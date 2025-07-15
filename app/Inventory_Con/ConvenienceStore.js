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

function ConvenienceInventory() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("all");
  const [convenienceLocationId, setConvenienceLocationId] = useState(null);

  const API_BASE_URL = "http://localhost/enguio/Api/backend.php";

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
          setConvenienceLocationId(convenienceLocation.location_id);
          return convenienceLocation.location_id;
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
      const response = await handleApiCall("get_location_products", {
        location_id: convenienceLocationId,
        search: searchTerm,
        category: selectedCategory
      });
      
      if (response.success && Array.isArray(response.data)) {
        console.log("✅ Loaded convenience store products:", response.data.length);
        setProducts(response.data);
      } else {
        console.warn("⚠️ No products found for convenience store");
        setProducts([]);
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

      {/* Store Inventory Overview */}
      <div className="bg-white rounded-xl shadow-md p-8 mb-8">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-gray-900">STORE INVENTORY OVERVIEW</h2>
          {/* Search Bar */}
          <div className="flex items-center w-80">
            <input
              type="text"
              placeholder="Search store inventory..."
              value={searchTerm}
              onChange={e => setSearchTerm(e.target.value)}
              className="flex-1 border border-gray-300 rounded-l-lg px-4 py-2 focus:outline-none bg-gray-50"
            />
            <button className="bg-gray-200 border border-l-0 border-gray-300 rounded-r-lg px-4 py-2">
              <Search className="h-5 w-5 text-gray-600" />
            </button>
          </div>
        </div>
        {/* Inventory Table */}
        <div className="overflow-x-auto">
          <table className="w-full border text-sm bg-white rounded-lg">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-3 py-2 border font-semibold text-gray-700">Barcode</th>
                <th className="px-3 py-2 border font-semibold text-gray-700">Product Name</th>
                <th className="px-3 py-2 border font-semibold text-gray-700">Category</th>
                <th className="px-3 py-2 border font-semibold text-gray-700">Quantity</th>
                <th className="px-3 py-2 border font-semibold text-gray-700">Unit Price</th>
                <th className="px-3 py-2 border font-semibold text-gray-700">Total Value</th>
                <th className="px-3 py-2 border font-semibold text-gray-700">Status</th>
                <th className="px-3 py-2 border font-semibold text-gray-700">Product Type</th>
                <th className="px-3 py-2 border font-semibold text-gray-700">Transfer Info</th>
                <th className="px-3 py-2 border font-semibold text-gray-700">Supplier Name</th>
                <th className="px-3 py-2 border font-semibold text-gray-700">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={11} className="text-center py-8">Loading...</td>
                </tr>
              ) : paginatedProducts.length > 0 ? (
                paginatedProducts.map(product => (
                  <tr key={`${product.product_id}-${product.product_type}`} className="hover:bg-gray-50 transition-colors">
                    <td className="border px-3 py-2 font-mono font-semibold">{product.barcode}</td>
                    <td className="border px-3 py-2">{product.product_name}</td>
                    <td className="border px-3 py-2">{product.category}</td>
                    <td className="border px-3 py-2 text-center">{product.quantity}</td>
                    <td className="border px-3 py-2 text-center">₱{Number(product.unit_price).toFixed(2)}</td>
                    <td className="border px-3 py-2 text-center">₱{(Number(product.unit_price) * Number(product.quantity)).toFixed(2)}</td>
                    <td className="border px-3 py-2 text-center">
                      <span className={`px-2 py-1 rounded text-xs font-semibold ${getStatusColor(product.stock_status)}`}>{product.stock_status ? product.stock_status.charAt(0).toUpperCase() + product.stock_status.slice(1) : 'Unknown'}</span>
                    </td>
                    <td className="border px-3 py-2 text-center">
                      <span className={`px-2 py-1 rounded text-xs font-semibold ${
                        product.product_type === 'Transferred' 
                          ? 'text-blue-600 bg-blue-100' 
                          : 'text-green-600 bg-green-100'
                      }`}>
                        {product.product_type}
                      </span>
                    </td>
                    <td className="border px-3 py-2 text-center">
                      {product.product_type === 'Transferred' ? (
                        <div className="text-xs">
                          <div className="font-semibold">From: {product.source_location}</div>
                          <div>By: {product.transferred_by}</div>
                          <div>{new Date(product.transfer_date).toLocaleDateString()}</div>
                        </div>
                      ) : (
                        <span className="text-gray-400">-</span>
                      )}
                    </td>
                    <td className="border px-3 py-2">{product.supplier_name || 'N/A'}</td>
                    <td className="border px-3 py-2 text-center">
                      <button className="text-gray-700 hover:text-black px-2 py-1 rounded">
                        <span className="text-xl">&#8226;&#8226;&#8226;</span>
                      </button>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={11} className="text-center py-8 text-gray-500">No products found</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        {/* Pagination Controls */}
        <div className="flex justify-end items-center gap-2 mt-4">
          <button
            onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
            disabled={currentPage === 1}
            className="px-3 py-1 border rounded bg-gray-100 text-gray-700 disabled:opacity-50"
          >
            {'< Previous'}
          </button>
          <span className="text-sm">Page {currentPage} of {totalPages}</span>
          <button
            onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))}
            disabled={currentPage === totalPages}
            className="px-3 py-1 border rounded bg-gray-100 text-gray-700 disabled:opacity-50"
          >
            {'Next >'}
          </button>
        </div>
      </div>


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
