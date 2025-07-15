"use client";
import React, { useState, useEffect } from "react";
import { toast, ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import { 
  FaPlus, 
  FaSearch, 
  FaEdit, 
  FaTrash, 
  FaEye, 
  FaFilter, 
  FaDownload, 
  FaUpload,
  FaBell,
  FaTruck,
  FaCheckCircle,
  FaExclamationTriangle,
  FaClock
} from "react-icons/fa";
import { Bell, Package, Truck, CheckCircle, AlertCircle, Clock } from "lucide-react";

const PharmacyInventory = () => {
  const [inventory, setInventory] = useState([]);
  const [filteredInventory, setFilteredInventory] = useState([]);
  const [notifications, setNotifications] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("all");
  const [page, setPage] = useState(1);
  const [rowsPerPage] = useState(10);
  const [isLoading, setIsLoading] = useState(false);
  const [showNotifications, setShowNotifications] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);
  const [pharmacyLocationId, setPharmacyLocationId] = useState(null);

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

  // Load pharmacy location ID
  const loadPharmacyLocation = async () => {
    try {
      const response = await handleApiCall("get_locations");
      if (response.success && Array.isArray(response.data)) {
        const pharmacyLocation = response.data.find(loc => 
          loc.location_name.toLowerCase().includes('pharmacy')
        );
        if (pharmacyLocation) {
          setPharmacyLocationId(pharmacyLocation.location_id);
          return pharmacyLocation.location_id;
        }
      }
    } catch (error) {
      console.error("Error loading pharmacy location:", error);
    }
    return null;
  };

  // Load products for pharmacy
  const loadProducts = async () => {
    if (!pharmacyLocationId) return;
    
    setIsLoading(true);
    try {
      const response = await handleApiCall("get_location_products", {
        location_id: pharmacyLocationId,
        search: searchTerm,
        category: selectedCategory
      });
      
      if (response.success && Array.isArray(response.data)) {
        console.log("✅ Loaded pharmacy products:", response.data.length);
        setInventory(response.data);
        setFilteredInventory(response.data);
      } else {
        console.warn("⚠️ No products found for pharmacy");
        setInventory([]);
        setFilteredInventory([]);
      }
    } catch (error) {
      console.error("Error loading products:", error);
      toast.error("Failed to load products");
      setInventory([]);
      setFilteredInventory([]);
    } finally {
      setIsLoading(false);
    }
  };

  // Load notifications for pharmacy
  const loadNotifications = async () => {
    if (!pharmacyLocationId) return;
    
    try {
      const response = await handleApiCall("get_notifications", {
        location_id: pharmacyLocationId,
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
        notes: "Transfer accepted by pharmacy"
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
      const locationId = await loadPharmacyLocation();
      if (locationId) {
        await loadProducts();
        await loadNotifications();
      }
    };
    initialize();
  }, [pharmacyLocationId]);

  useEffect(() => {
    if (pharmacyLocationId) {
      loadProducts();
    }
  }, [searchTerm, selectedCategory, pharmacyLocationId]);

  useEffect(() => {
    filterInventory();
  }, [searchTerm, selectedCategory, inventory]);

  const filterInventory = () => {
    let filtered = inventory;

    if (searchTerm) {
      filtered = filtered.filter(item =>
        item.product_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.category.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.barcode.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    if (selectedCategory !== "all") {
      filtered = filtered.filter(item => item.category === selectedCategory);
    }

    setFilteredInventory(filtered);
  };

  const getStatusColor = (status) => {
    switch (status) {
      case "in stock":
        return "success";
      case "low stock":
        return "warning";
      case "out of stock":
        return "danger";
      default:
        return "default";
    }
  };

  const getNotificationIcon = (type) => {
    switch (type) {
      case "transfer":
        return <FaTruck className="h-5 w-5 text-blue-500" />;
      case "low_stock":
        return <FaExclamationTriangle className="h-5 w-5 text-yellow-500" />;
      case "expiry":
        return <FaClock className="h-5 w-5 text-red-500" />;
      default:
        return <FaBell className="h-5 w-5 text-gray-500" />;
    }
  };

  const categories = [...new Set(inventory.map(p => p.category).filter(Boolean))];
  const pages = Math.ceil(filteredInventory.length / rowsPerPage);
  const items = filteredInventory.slice((page - 1) * rowsPerPage, page * rowsPerPage);

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Pharmacy Inventory</h1>
          <p className="text-gray-600">Manage pharmaceutical products and medications</p>
        </div>
        <div className="flex gap-3">
          <div className="relative">
            <button
              onClick={() => setShowNotifications(!showNotifications)}
              className="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-colors"
            >
              <FaBell className="h-6 w-6" />
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
                      <FaBell className="h-8 w-8 mx-auto text-gray-300 mb-2" />
                      <p>No notifications</p>
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>
          <button className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center gap-2">
            <FaUpload className="h-4 w-4" />
            Import
          </button>
          <button className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center gap-2">
            <FaDownload className="h-4 w-4" />
            Export
          </button>
          <button className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center gap-2">
            <FaPlus className="h-4 w-4" />
            Add Product
          </button>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-lg border p-6">
          <div className="flex items-center">
            <Package className="h-8 w-8 text-blue-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Products</p>
              <p className="text-2xl font-bold text-gray-900">{inventory.length}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-lg border p-6">
          <div className="flex items-center">
            <CheckCircle className="h-8 w-8 text-green-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">In Stock</p>
              <p className="text-2xl font-bold text-gray-900">
                {inventory.filter(p => p.stock_status === 'in stock').length}
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-lg border p-6">
          <div className="flex items-center">
            <AlertCircle className="h-8 w-8 text-yellow-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Low Stock</p>
              <p className="text-2xl font-bold text-gray-900">
                {inventory.filter(p => p.stock_status === 'low stock').length}
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-lg border p-6">
          <div className="flex items-center">
            <Truck className="h-8 w-8 text-purple-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Pending Transfers</p>
              <p className="text-2xl font-bold text-gray-900">
                {notifications.filter(n => n.notification_type === 'transfer' && n.status === 'unread').length}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Filters and Search */}
      <div className="bg-white rounded-lg border p-6">
        <div className="flex flex-col md:flex-row gap-4">
          <div className="flex-1">
            <div className="relative">
              <FaSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
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
        </div>
      </div>

      {/* Inventory Table */}
      <div className="bg-white rounded-lg border">
        <div className="px-6 py-4 border-b border-gray-200">
          <div className="flex justify-between items-center">
            <h3 className="text-xl font-semibold text-gray-900">Products</h3>
            <div className="text-sm text-gray-500">
              {filteredInventory.length} products found
            </div>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  PRODUCT NAME
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
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  STATUS
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  ACTIONS
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {isLoading ? (
                <tr>
                  <td colSpan={8} className="px-6 py-4 text-center text-gray-500">
                    Loading products...
                  </td>
                </tr>
              ) : items.length > 0 ? (
                items.map((item) => (
                  <tr key={item.product_id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <div className="flex items-center">
                        <div className="flex-shrink-0 h-10 w-10">
                          <img
                            src={item.image || "/placeholder.svg?height=40&width=40"}
                            alt={item.product_name}
                            className="h-10 w-10 rounded object-cover"
                          />
                        </div>
                        <div className="ml-4">
                          <div className="text-sm font-medium text-gray-900">
                            {item.product_name}
                          </div>
                          {item.Variation && (
                            <div className="text-sm text-gray-500">
                              {item.Variation}
                            </div>
                          )}
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      <span className="inline-flex px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                        {item.category}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-center">
                      <div>
                        <div className="font-semibold">{item.quantity || 0}</div>
                        <div className="text-sm text-gray-500">units</div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-center text-sm text-gray-900">
                      ₱{Number.parseFloat(item.unit_price || 0).toFixed(2)}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      {item.supplier_name || "N/A"}
                    </td>
                    <td className="px-6 py-4 text-sm font-mono text-gray-900">
                      {item.barcode}
                    </td>
                    <td className="px-6 py-4 text-center">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        item.stock_status === "in stock"
                          ? "bg-green-100 text-green-800"
                          : item.stock_status === "low stock"
                            ? "bg-yellow-100 text-yellow-800"
                            : item.stock_status === "out of stock"
                              ? "bg-red-100 text-red-800"
                              : "bg-gray-100 text-gray-800"
                      }`}>
                        {item.stock_status || "unknown"}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-center">
                      <div className="flex justify-center gap-2">
                        <button className="text-blue-600 hover:text-blue-900 p-1">
                          <FaEdit className="h-4 w-4" />
                        </button>
                        <button className="text-green-600 hover:text-green-900 p-1">
                          <FaEye className="h-4 w-4" />
                        </button>
                        <button className="text-red-600 hover:text-red-900 p-1">
                          <FaTrash className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={8} className="px-6 py-8 text-center">
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
        {pages > 1 && (
          <div className="flex justify-center mt-4 pb-4">
            <div className="flex items-center space-x-2">
              <button
                onClick={() => setPage(Math.max(1, page - 1))}
                disabled={page === 1}
                className="px-3 py-1 border border-gray-300 rounded disabled:opacity-50"
              >
                Previous
              </button>
              <span className="px-3 py-1 text-sm">
                Page {page} of {pages}
              </span>
              <button
                onClick={() => setPage(Math.min(pages, page + 1))}
                disabled={page === pages}
                className="px-3 py-1 border border-gray-300 rounded disabled:opacity-50"
              >
                Next
              </button>
            </div>
          </div>
        )}
      </div>

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
};

export default PharmacyInventory; 