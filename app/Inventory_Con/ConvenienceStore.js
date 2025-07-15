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
  Bell,
  Package,
  Truck,
  CheckCircle,
  AlertCircle,
  Clock,
} from "lucide-react";

function ConvenienceInventory() {
  const [products, setProducts] = useState([]);
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("all");
  const [showNotifications, setShowNotifications] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);
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

  // Load notifications for convenience store
  const loadNotifications = async () => {
    if (!convenienceLocationId) return;
    
    try {
      const response = await handleApiCall("get_notifications", {
        location_id: convenienceLocationId,
        status: "all"
      });
      
      if (response.success && Array.isArray(response.data)) {
        console.log("✅ Loaded notifications:", response.data.length);
        setNotifications(response.data);
        setUnreadCount(response.data.filter(n => n.status === 'unread').length);
      } else {
        setNotifications([]);
        setUnreadCount(0);
      }
    } catch (error) {
      console.error("Error loading notifications:", error);
      setNotifications([]);
      setUnreadCount(0);
    }
  };

  // Mark notification as read
  const markNotificationRead = async (notificationId) => {
    try {
      const response = await handleApiCall("mark_notification_read", {
        notification_id: notificationId
      });
      
      if (response.success) {
        // Update local state
        setNotifications(prev => 
          prev.map(n => 
            n.notification_id === notificationId 
              ? { ...n, status: 'read' } 
              : n
          )
        );
        setUnreadCount(prev => Math.max(0, prev - 1));
        toast.success("Notification marked as read");
      }
    } catch (error) {
      console.error("Error marking notification as read:", error);
      toast.error("Failed to mark notification as read");
    }
  };

  // Accept transfer (mark as completed)
  const acceptTransfer = async (transferId) => {
    try {
      const response = await handleApiCall("update_transfer_status", {
        transfer_header_id: transferId,
        status: "Completed",
        employee_id: 1, // You can get this from user session
        notes: "Transfer accepted by convenience store"
      });

      if (response.success) {
        toast.success("Transfer accepted successfully!");
        loadProducts(); // Reload products to show new inventory
        loadNotifications(); // Reload notifications
      } else {
        toast.error(response.message || "Failed to accept transfer");
      }
    } catch (error) {
      console.error("Error accepting transfer:", error);
      toast.error("Failed to accept transfer");
    }
  };

  useEffect(() => {
    const initialize = async () => {
      const locationId = await loadConvenienceLocation();
      if (locationId) {
        await loadProducts();
        await loadNotifications();
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

  const getNotificationIcon = (type) => {
    switch (type) {
      case "transfer":
        return <Truck className="h-5 w-5 text-blue-500" />;
      case "low_stock":
        return <AlertCircle className="h-5 w-5 text-yellow-500" />;
      case "expiry":
        return <Clock className="h-5 w-5 text-red-500" />;
      default:
        return <Bell className="h-5 w-5 text-gray-500" />;
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
          <div className="flex items-center gap-4">
            {/* Notification Bell */}
            <div className="relative">
              <button
                onClick={() => setShowNotifications(!showNotifications)}
                className="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-colors"
              >
                <Bell className="h-6 w-6" />
                {unreadCount > 0 && (
                  <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                    {unreadCount}
                  </span>
                )}
              </button>
              
              {/* Notifications Dropdown */}
              {showNotifications && (
                <div className="absolute right-0 top-12 w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50 max-h-96 overflow-y-auto">
                  <div className="p-4 border-b border-gray-200">
                    <h3 className="text-lg font-semibold text-gray-900">Notifications</h3>
                    <p className="text-sm text-gray-600">{unreadCount} unread</p>
                  </div>
                  <div className="divide-y divide-gray-200">
                    {notifications.length > 0 ? (
                      notifications.map((notification) => (
                        <div
                          key={notification.notification_id}
                          className={`p-4 hover:bg-gray-50 transition-colors ${
                            notification.status === 'unread' ? 'bg-blue-50' : ''
                          }`}
                        >
                          <div className="flex items-start gap-3">
                            {getNotificationIcon(notification.notification_type)}
                            <div className="flex-1">
                              <p className="text-sm text-gray-900">{notification.message}</p>
                              <p className="text-xs text-gray-500 mt-1">
                                {new Date(notification.created_at).toLocaleString()}
                              </p>
                              {notification.notification_type === 'transfer' && notification.transfer_id && (
                                <div className="mt-2 flex gap-2">
                                  <button
                                    onClick={() => acceptTransfer(notification.transfer_id)}
                                    className="text-xs bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600"
                                  >
                                    Accept Transfer
                                  </button>
                                  <button
                                    onClick={() => markNotificationRead(notification.notification_id)}
                                    className="text-xs bg-gray-500 text-white px-2 py-1 rounded hover:bg-gray-600"
                                  >
                                    Mark Read
                                  </button>
                                </div>
                              )}
                            </div>
                            {notification.status === 'unread' && (
                              <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                            )}
                          </div>
                        </div>
                      ))
                    ) : (
                      <div className="p-4 text-center text-gray-500">
                        <Bell className="h-8 w-8 mx-auto text-gray-300 mb-2" />
                        <p>No notifications</p>
                      </div>
                    )}
                  </div>
                </div>
              )}
            </div>
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
                <th className="px-3 py-2 border font-semibold text-gray-700">Supplier Name</th>
                <th className="px-3 py-2 border font-semibold text-gray-700">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={9} className="text-center py-8">Loading...</td>
                </tr>
              ) : paginatedProducts.length > 0 ? (
                paginatedProducts.map(product => (
                  <tr key={product.product_id} className="hover:bg-gray-50 transition-colors">
                    <td className="border px-3 py-2 font-mono font-semibold">{product.barcode}</td>
                    <td className="border px-3 py-2">{product.product_name}</td>
                    <td className="border px-3 py-2">{product.category}</td>
                    <td className="border px-3 py-2 text-center">{product.quantity}</td>
                    <td className="border px-3 py-2 text-center">₱{Number(product.unit_price).toFixed(2)}</td>
                    <td className="border px-3 py-2 text-center">₱{(Number(product.unit_price) * Number(product.quantity)).toFixed(2)}</td>
                    <td className="border px-3 py-2 text-center">
                      <span className={`px-2 py-1 rounded text-xs font-semibold ${getStatusColor(product.stock_status)}`}>{product.stock_status ? product.stock_status.charAt(0).toUpperCase() + product.stock_status.slice(1) : 'Unknown'}</span>
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
                  <td colSpan={9} className="text-center py-8 text-gray-500">No products found</td>
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
