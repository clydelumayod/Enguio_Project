"use client";

import React, { useState, useEffect } from "react";
import axios from "axios";
import { toast, ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import {
  ChevronUp,
  ChevronDown,
  Plus,
  X,
  Search,
  Package,
  Truck,
  CheckCircle,
  AlertCircle,
  Clock,
} from "lucide-react";
import { FaArchive } from "react-icons/fa";

function ConvenienceInventory() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("all");
  const [convenienceLocationId, setConvenienceLocationId] = useState(null);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [selectedItem, setSelectedItem] = useState(null);
  const [archiveLoading, setArchiveLoading] = useState(false);

  const API_BASE_URL = "http://localhost/Enguio_Project/Api/backend.php";

  // API function
  async function handleApiCall(action, data = {}) {
    const payload = { action, ...data };
    console.log("🚀 API Call Payload:", payload);

    try {
      const response = await fetch(API_BASE_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      const resData = await response.json();
      console.log("✅ API Success Response:", resData);

      if (resData && typeof resData === "object") {
        if (!resData.success) {
          console.warn("⚠️ API responded with failure:", resData.message || resData);
        }
        return resData;
      } else {
        console.warn("⚠️ Unexpected API response format:", resData);
        return {
          success: false,
          message: "Unexpected response format",
          data: resData,
        };
      }
    } catch (error) {
      console.error("❌ API Call Error:", error);
      return {
        success: false,
        message: error.message,
        error: "REQUEST_ERROR",
      };
    }
  }

  // Load convenience store location ID
  const loadConvenienceLocation = async () => {
    try {
      const response = await handleApiCall("get_locations");
      if (response.success && Array.isArray(response.data)) {
        const convenienceLocation = response.data.find(loc => 
          loc.location_name.toLowerCase().includes('convenience')
        );
        if (convenienceLocation) {
          console.log("📍 Found convenience location:", convenienceLocation);
          setConvenienceLocationId(convenienceLocation.location_id);
          return convenienceLocation.location_id;
        } else {
          console.warn("⚠️ No convenience store location found");
        }
      }
    } catch (error) {
      console.error("Error loading convenience location:", error);
    }
    return null;
  };

  // Load products for convenience store
  const loadProducts = async () => {
    if (!convenienceLocationId) return;
    
    setLoading(true);
    try {
      console.log("🔄 Loading convenience store products...");
      
      // Try the location products API that includes transfer information
      const response = await handleApiCall("get_location_products", {
        location_id: convenienceLocationId,
        search: searchTerm,
        category: selectedCategory
      });
      
      console.log("📦 API Response:", response);
      
      if (response.success && Array.isArray(response.data)) {
        console.log("✅ Loaded convenience store products:", response.data.length);
        // Filter out archived products
        const activeProducts = response.data.filter(
          (product) => (product.status || "").toLowerCase() !== "archived"
        );
        console.log("✅ Active convenience store products after filtering:", activeProducts.length);
        console.log("📋 Products:", activeProducts.map(p => `${p.product_name} (${p.quantity}) - ${p.product_type}`));
        setProducts(activeProducts);
      } else {
        console.warn("⚠️ Primary API failed, trying fallback...");
        // Fallback to the location name API
        const fallbackResponse = await handleApiCall("get_products_by_location_name", {
          location_name: "Convenience"
        });
        
        if (fallbackResponse.success && Array.isArray(fallbackResponse.data)) {
          console.log("✅ Loaded convenience store products (fallback):", fallbackResponse.data.length);
          // Filter out archived products
          const activeProducts = fallbackResponse.data.filter(
            (product) => (product.status || "").toLowerCase() !== "archived"
          );
          console.log("✅ Active convenience store products after filtering (fallback):", activeProducts.length);
          setProducts(activeProducts);
        } else {
          console.warn("⚠️ No products found for convenience store");
          setProducts([]);
        }
      }
    } catch (error) {
      console.error("Error loading products:", error);
      toast.error("Failed to load products");
      setProducts([]);
    } finally {
      setLoading(false);
    }
  };



  useEffect(() => {
    const initialize = async () => {
      const locationId = await loadConvenienceLocation();
      if (locationId) {
        await loadProducts();
      }
    };
    initialize();
  }, [convenienceLocationId]);

  useEffect(() => {
    if (convenienceLocationId) {
      loadProducts();
    }
  }, [searchTerm, selectedCategory, convenienceLocationId]);

  // Auto-refresh products every 30 seconds to catch new transfers
  useEffect(() => {
    const interval = setInterval(() => {
      if (convenienceLocationId && !loading) {
        console.log("🔄 Auto-refreshing convenience store products...");
        const previousCount = products.length;
        loadProducts().then(() => {
          // Check if new products were added
          if (products.length > previousCount) {
            const newProducts = products.length - previousCount;
            toast.success(`🆕 ${newProducts} new product(s) transferred to convenience store!`);
          }
        });
      }
    }, 30000); // 30 seconds

    return () => clearInterval(interval);
  }, [convenienceLocationId, loading]);

  const getStatusColor = (status) => {
    switch (status) {
      case "in stock":
        return "text-green-600 bg-green-100";
      case "low stock":
        return "text-yellow-600 bg-yellow-100";
      case "out of stock":
        return "text-red-600 bg-red-100";
      default:
        return "text-gray-600 bg-gray-100";
    }
  };

  // Archive functionality
  const openDeleteModal = (item) => {
    setSelectedItem(item);
    setShowDeleteModal(true);
  };

  const closeDeleteModal = () => {
    setShowDeleteModal(false);
    setSelectedItem(null);
  };

  const handleDeleteItem = async () => {
    if (!selectedItem) return;
    
    setArchiveLoading(true);
    try {
      const response = await handleApiCall("delete_product", {
        product_id: selectedItem.product_id,
        reason: "Archived from convenience store inventory",
        archived_by: "admin"
      });
      
      if (response.success) {
        toast.success("Product archived successfully");
        closeDeleteModal();
        loadProducts(); // Reload products
      } else {
        toast.error(response.message || "Failed to archive product");
      }
    } catch (error) {
      console.error("Error archiving product:", error);
      toast.error("Failed to archive product");
    } finally {
      setArchiveLoading(false);
    }
  };

  const categories = [...new Set(products.map(p => p.category).filter(Boolean))];

  // --- Dashboard Statistics Calculation ---
  // Calculate total store value
  const totalStoreValue = products.reduce(
    (sum, p) => sum + (Number(p.unit_price || 0) * Number(p.quantity || 0)),
    0
  );
  // For demo, use static percentage changes
  const percentChangeProducts = 3; // +3% from last month
  const percentChangeValue = 1; // +1% from last month
  // Low stock count
  const lowStockCount = products.filter(p => p.stock_status === 'low stock').length;

  // --- Pagination State ---
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;
  const paginatedProducts = products.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );
  const totalPages = Math.ceil(products.length / itemsPerPage);

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-6">
        <div className="flex items-center text-sm text-gray-600 mb-2">
          <span>Inventory Management</span>
          <div className="mx-2">{">"}</div>
          <span className="text-blue-600">Convenience Store</span>
        </div>
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Convenience Store Inventory</h1>
            <p className="text-gray-600">Manage convenience store products and transfers</p>
          </div>
        </div>
      </div>

      {/* Dashboard Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        {/* Store Products */}
        <div className="bg-white rounded-xl shadow-md p-6 flex justify-between items-center min-h-[110px]">
          <div>
            <div className="text-xs text-gray-600 font-medium mb-1">STORE PRODUCTS</div>
            <div className="text-4xl font-bold text-gray-900">{products.length}</div>
            <div className="text-xs text-gray-500 mt-2">+{percentChangeProducts}% from last month</div>
          </div>
          <div>
            <Package className="h-10 w-10 text-blue-600" />
          </div>
        </div>
        {/* Low Stock Items */}
        <div className="bg-white rounded-xl shadow-md p-6 flex justify-between items-center min-h-[110px]">
          <div>
            <div className="text-xs text-gray-600 font-medium mb-1">LOW STOCK ITEMS</div>
            <div className="text-4xl font-bold text-gray-900">{lowStockCount}</div>
            <div className="text-xs text-gray-500 mt-2">items below threshold</div>
          </div>
          <div>
            <AlertCircle className="h-10 w-10 text-red-500" />
          </div>
        </div>
        {/* Store Value */}
        <div className="bg-white rounded-xl shadow-md p-6 flex justify-between items-center min-h-[110px]">
          <div>
            <div className="text-xs text-gray-600 font-medium mb-1">STORE VALUE</div>
            <div className="text-4xl font-bold text-gray-900">₱{totalStoreValue.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2})}</div>
            <div className="text-xs text-gray-500 mt-2">+{percentChangeValue}% from last month</div>
          </div>
          <div>
            <Package className="h-10 w-10 text-orange-500" />
          </div>
        </div>
      </div>

      {/* Filters and Search */}
      <div className="bg-white rounded-3xl shadow-xl p-6 mb-6">
        <div className="flex flex-col md:flex-row gap-4">
          <div className="flex-1">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <input
                type="text"
                placeholder="Search products..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
          <div className="w-full md:w-48">
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="all">All Categories</option>
              {categories.map((category) => (
                <option key={category} value={category}>
                  {category}
                </option>
              ))}
            </select>
          </div>
          <button
            onClick={loadProducts}
            disabled={loading}
            className="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-lg flex items-center gap-2"
          >
            <Package className="h-4 w-4" />
            {loading ? "Refreshing..." : "Refresh"}
          </button>
        </div>
      </div>

      {/* Inventory Table */}
      <div className="bg-white rounded-3xl shadow-xl">
        <div className="px-6 py-4 border-b border-gray-200">
          <div className="flex justify-between items-center">
            <h3 className="text-xl font-semibold text-gray-900">Store Products</h3>
            <div className="text-sm text-gray-500">
              {products.length} products found
            </div>
          </div>
        </div>
        <div className="overflow-x-auto max-h-96">
          <table className="w-full min-w-max">
            <thead className="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  PRODUCT NAME
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  BRAND
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  CATEGORY
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  STOCK
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  PRICE
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  SUPPLIER
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  BARCODE
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  BATCH NO.
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  BATCH DATE
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  STATUS
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  TRANSFER DETAILS
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  ACTIONS
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {loading ? (
                <tr>
                  <td colSpan={12} className="px-6 py-4 text-center text-gray-500">
                    Loading products...
                  </td>
                </tr>
              ) : paginatedProducts.length > 0 ? (
                paginatedProducts.map((product, index) => (
                  <tr key={`${product.product_id}-${index}`} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <div className="text-sm font-medium text-gray-900">
                        {product.product_name}
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      {product.brand || 'N/A'}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      <span className="inline-flex px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                        {product.category}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-center">
                      <div>
                        <div className="font-semibold">{product.quantity || 0}</div>
                        <div className="text-sm text-gray-500">units</div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-center text-sm text-gray-900">
                      ₱{Number.parseFloat(product.unit_price || 0).toFixed(2)}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      {product.supplier_name || "N/A"}
                    </td>
                    <td className="px-6 py-4 text-sm font-mono text-gray-900">
                      {product.barcode}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      <div>
                        <div className="font-medium">{product.batch_reference || <span className="text-gray-400 italic">None</span>}</div>
                        {product.entry_by && (
                          <div className="text-xs text-gray-500">by {product.entry_by}</div>
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4 text-center text-sm text-gray-900">
                      {product.entry_date ? new Date(product.entry_date).toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: '2-digit' }) : <span className="text-gray-400 italic">N/A</span>}
                    </td>
                    <td className="px-6 py-4 text-center">
                       <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                         product.stock_status === "in stock"
                           ? "bg-green-100 text-green-800"
                           : product.stock_status === "low stock"
                             ? "bg-yellow-100 text-yellow-800"
                             : product.stock_status === "out of stock"
                               ? "bg-red-100 text-red-800"
                               : "bg-gray-100 text-gray-800"
                       }`}>
                         {product.stock_status || "unknown"}
                       </span>
                     </td>
                     <td className="px-6 py-4 text-center">
                       {product.product_type === 'Transferred' ? (
                         <div className="text-xs text-gray-600">
                           <div className="font-semibold text-blue-600">From: {product.source_location}</div>
                           <div>By: {product.transferred_by}</div>
                           <div>{new Date(product.transfer_date).toLocaleDateString()}</div>
                           <div className="mt-1">
                             <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                               Transferred
                             </span>
                           </div>
                         </div>
                       ) : (
                         <div className="text-xs text-gray-600">
                           <div className="font-semibold text-green-600">From: {product.source_location || 'Warehouse'}</div>
                           <div className="mt-1">
                             <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                               Direct Stock
                             </span>
                           </div>
                         </div>
                       )}
                     </td>
                    <td className="px-6 py-4 text-center">
                      <div className="flex justify-center gap-2">
                        <button 
                          onClick={() => openDeleteModal(product)}
                          className="text-red-600 hover:text-red-900 p-1"
                          title="Archive Product"
                        >
                          <FaArchive className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
                              ) : (
                  <tr>
                    <td colSpan={12} className="px-6 py-8 text-center">
                      <div className="flex flex-col items-center space-y-3">
                        <Package className="h-12 w-12 text-gray-300" />
                        <div className="text-gray-500">
                          <p className="text-lg font-medium">No products found</p>
                          <p className="text-sm">Products will appear here when transferred from warehouse</p>
                        </div>
                      </div>
                    </td>
                  </tr>
                )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex justify-center mt-4 pb-4">
            <div className="flex items-center space-x-2">
              <button
                onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
                disabled={currentPage === 1}
                className="px-3 py-1 border border-gray-300 rounded disabled:opacity-50"
              >
                Previous
              </button>
              <span className="px-3 py-1 text-sm">
                Page {currentPage} of {totalPages}
              </span>
              <button
                onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
                disabled={currentPage === totalPages}
                className="px-3 py-1 border border-gray-300 rounded disabled:opacity-50"
              >
                Next
              </button>
            </div>
          </div>
        )}
      </div>


      {/* Delete Confirmation Modal */}
      {showDeleteModal && (
        <div className="fixed inset-0 backdrop-blur-md flex items-center justify-center z-50">
          <div className="bg-white/90 backdrop-blur-md rounded-xl shadow-2xl p-6 border border-gray-200/50 w-96">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Confirm Archive</h3>
            <p className="text-gray-700 mb-4">Are you sure you want to archive this product?</p>
            <div className="flex justify-end space-x-4">
              <button
                type="button"
                onClick={closeDeleteModal}
                className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleDeleteItem}
                className="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-md disabled:opacity-50"
              >
                {archiveLoading ? "Archiving..." : "Archive"}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Keep ToastContainer for notifications */}
      <ToastContainer
        position="top-right"
        autoClose={3000}
        hideProgressBar={false}
        newestOnTop={false}
        closeOnClick
        rtl={false}
        pauseOnFocusLoss
        draggable
        pauseOnHover
      />
    </div>
  );
}

export default ConvenienceInventory;
