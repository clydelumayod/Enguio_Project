"use client";
import React, { useState, useEffect } from "react";
import { toast, ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import { 
  FaSearch, 
  FaEye, 
  FaFilter, 
  FaDownload, 
  FaCalendar, 
  FaArchive, 
  FaTrash, 
  FaUndo, 
  FaHistory, 
  FaBox 
} from "react-icons/fa";
import { Archive as ArchiveIcon, Trash2, RotateCcw, FileText, Clock, AlertCircle } from "lucide-react";

const Archive = () => {
  const [archivedItems, setArchivedItems] = useState([]);
  const [filteredItems, setFilteredItems] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedType, setSelectedType] = useState("all");
  const [selectedDateRange, setSelectedDateRange] = useState("all");
  const [page, setPage] = useState(1);
  const [rowsPerPage] = useState(10);
  const [selectedItem, setSelectedItem] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [showModal, setShowModal] = useState(false);

  // API call function (copied from Warehouse.js for consistency)
  async function handleApiCall(action, data = {}) {
    const API_BASE_URL = "http://localhost/Enguio_Project/Api/backend.php";
    const payload = { action, ...data };
    try {
      const response = await fetch(API_BASE_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const resData = await response.json();
      return resData;
    } catch (error) {
      console.error("❌ API Call Error:", error);
      return { success: false, message: error.message, error: "REQUEST_ERROR" };
    }
  }

  // Fetch archived items from backend on mount
  useEffect(() => {
    async function fetchArchivedItems() {
      setIsLoading(true);
      try {
        const response = await handleApiCall("get_archived_items");
        if (response.success && Array.isArray(response.data)) {
          setArchivedItems(response.data);
          setFilteredItems(response.data);
        } else {
          setArchivedItems([]);
          setFilteredItems([]);
        }
      } catch (error) {
        setArchivedItems([]);
        setFilteredItems([]);
      } finally {
        setIsLoading(false);
      }
    }
    fetchArchivedItems();
  }, []);

  useEffect(() => {
    filterItems();
  }, [searchTerm, selectedType, selectedDateRange, archivedItems]);

  const filterItems = () => {
    let filtered = archivedItems;

    if (searchTerm) {
      filtered = filtered.filter(item =>
        item.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.category.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.archivedBy.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.reason.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    if (selectedType !== "all") {
      filtered = filtered.filter(item => item.type === selectedType);
    }

    if (selectedDateRange !== "all") {
      const today = new Date();
      const filteredDate = new Date();
      
      switch (selectedDateRange) {
        case "today":
          filtered = filtered.filter(item => item.archivedDate === today.toISOString().split('T')[0]);
          break;
        case "week":
          filteredDate.setDate(today.getDate() - 7);
          filtered = filtered.filter(item => new Date(item.archivedDate) >= filteredDate);
          break;
        case "month":
          filteredDate.setMonth(today.getMonth() - 1);
          filtered = filtered.filter(item => new Date(item.archivedDate) >= filteredDate);
          break;
        default:
          break;
      }
    }

    setFilteredItems(filtered);
  };

  const getStatusColor = (status) => {
    switch (status) {
      case "Archived":
        return "bg-gray-100 text-gray-800";
      case "Deleted":
        return "bg-red-100 text-red-800";
      case "Restored":
        return "bg-green-100 text-green-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  };

  const getTypeColor = (type) => {
    switch (type) {
      case "Product":
        return "bg-blue-100 text-blue-800";
      case "Category":
        return "bg-purple-100 text-purple-800";
      case "Supplier":
        return "bg-orange-100 text-orange-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  };

  const handleViewDetails = (item) => {
    setSelectedItem(item);
    setShowModal(true);
  };

  const handleRestore = async (id) => {
    try {
      const response = await handleApiCall("restore_archived_item", { id });
      if (response.success) {
        setArchivedItems(prev => prev.filter(item => item.id !== id));
        toast.success('Item restored successfully');
      } else {
        toast.error(response.message || 'Failed to restore item');
      }
    } catch (error) {
      toast.error('Error restoring item');
    }
  };

  const handleDelete = async (id) => {
    if (window.confirm('Are you sure you want to permanently delete this item? This action cannot be undone.')) {
      try {
        const response = await handleApiCall("delete_archived_item", { id });
        if (response.success) {
          setArchivedItems(prev => prev.filter(item => item.id !== id));
          toast.success('Item permanently deleted');
        } else {
          toast.error(response.message || 'Failed to delete item');
        }
      } catch (error) {
        toast.error('Error deleting item');
      }
    }
  };

  const itemTypes = ["all", "Product", "Category", "Supplier"];
  const dateRanges = ["all", "today", "week", "month"];

  const pages = Math.ceil(filteredItems.length / rowsPerPage);
  const items = filteredItems.slice((page - 1) * rowsPerPage, page * rowsPerPage);

  // Calculate statistics
  const totalArchived = filteredItems.length;
  const archivedProducts = filteredItems.filter(item => item.type === 'Product').length;
  const archivedCategories = filteredItems.filter(item => item.type === 'Category').length;
  const archivedSuppliers = filteredItems.filter(item => item.type === 'Supplier').length;

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Archive</h1>
          <p className="text-gray-600">Manage archived items and restoration</p>
        </div>
        <div className="flex gap-3">
          <button 
            onClick={() => window.print()}
            className="flex items-center gap-2 px-4 py-2 text-blue-600 hover:text-blue-900"
          >
            <FaDownload className="h-4 w-4" />
            Export
          </button>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <ArchiveIcon className="h-8 w-8 text-blue-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Archived</p>
              <p className="text-2xl font-bold text-gray-900">{totalArchived}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <FileText className="h-8 w-8 text-green-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Products</p>
              <p className="text-2xl font-bold text-gray-900">{archivedProducts}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <AlertCircle className="h-8 w-8 text-yellow-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Categories</p>
              <p className="text-2xl font-bold text-gray-900">{archivedCategories}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <Clock className="h-8 w-8 text-purple-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Suppliers</p>
              <p className="text-2xl font-bold text-gray-900">{archivedSuppliers}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Filters and Search */}
      <div className="bg-white rounded-3xl shadow-xl p-6">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="md:col-span-2">
            <div className="relative">
              <FaSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <input
                type="text"
                placeholder="Search archived items..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
          <div>
            <select
              value={selectedType}
              onChange={(e) => setSelectedType(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              {itemTypes.map((type) => (
                <option key={type} value={type}>
                  {type === "all" ? "All Types" : type}
                </option>
              ))}
            </select>
          </div>
          <div>
            <select
              value={selectedDateRange}
              onChange={(e) => setSelectedDateRange(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              {dateRanges.map((range) => (
                <option key={range} value={range}>
                  {range === "all" ? "All Time" : 
                   range === "today" ? "Today" :
                   range === "week" ? "Last 7 Days" :
                   range === "month" ? "Last 30 Days" : range}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Archived Items Table */}
      <div className="bg-white rounded-3xl shadow-xl">
        <div className="px-6 py-4 border-b border-gray-200">
          <div className="flex justify-between items-center">
            <h3 className="text-xl font-semibold text-gray-900">Archived Items</h3>
            <div className="text-sm text-gray-500">
              {isLoading ? (
                <div className="flex items-center gap-2">
                  <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div>
                  Loading...
                </div>
              ) : (
                `${filteredItems.length} items found`
              )}
            </div>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  ITEM NAME
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  TYPE
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  CATEGORY
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  ARCHIVED BY
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  DATE ARCHIVED
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
                  <td colSpan={7} className="px-6 py-4 text-center text-gray-500">
                    Loading archived items...
                  </td>
                </tr>
              ) : items.length > 0 ? (
                items.map((item) => (
                  <tr key={item.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <div>
                        <div className="text-sm font-medium text-gray-900">{item.name}</div>
                        <div className="text-sm text-gray-500">{item.description}</div>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <span className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${getTypeColor(item.type)}`}>
                        {item.type}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <span className="text-sm text-gray-900">{item.category}</span>
                    </td>
                    <td className="px-6 py-4">
                      <span className="text-sm text-gray-900">{item.archivedBy}</span>
                    </td>
                    <td className="px-6 py-4">
                      <div>
                        <div className="text-sm font-medium text-gray-900">{item.archivedDate}</div>
                        <div className="text-sm text-gray-500">{item.archivedTime}</div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-center">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(item.status)}`}>
                        {item.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-center">
                      <div className="flex justify-center gap-2">
                        <button 
                          onClick={() => handleViewDetails(item)}
                          className="text-blue-600 hover:text-blue-900 p-1"
                        >
                          <FaEye className="h-4 w-4" />
                        </button>
                        <button 
                          onClick={() => handleRestore(item.id)}
                          className="text-green-600 hover:text-green-900 p-1"
                        >
                          <FaUndo className="h-4 w-4" />
                        </button>
                        <button 
                          onClick={() => handleDelete(item.id)}
                          className="text-red-600 hover:text-red-900 p-1"
                        >
                          <FaTrash className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={7} className="px-6 py-8 text-center">
                    <div className="flex flex-col items-center space-y-3">
                      <FaBox className="h-12 w-12 text-gray-300" />
                      <div className="text-gray-500">
                        <p className="text-lg font-medium">No archived items found</p>
                        <p className="text-sm">Archived items will appear here</p>
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

      {/* Item Details Modal */}
      {showModal && (
        <div className="fixed inset-0 backdrop-blur-md flex items-center justify-center z-50">
          <div className="bg-white rounded-3xl shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div className="px-6 py-4 border-b border-gray-200">
              <div className="flex justify-between items-center">
                <h3 className="text-xl font-semibold text-gray-900">Item Details</h3>
                <button 
                  onClick={() => setShowModal(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
            <div className="p-6">
              {selectedItem && (
                <div className="space-y-6">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <h4 className="font-semibold text-gray-700 mb-3">Item Information</h4>
                      <div className="space-y-3">
                        <div>
                          <span className="text-sm text-gray-500">Name:</span>
                          <div className="font-medium text-gray-900">{selectedItem.name}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Type:</span>
                          <div className="font-medium text-gray-900">{selectedItem.type}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Category:</span>
                          <div className="font-medium text-gray-900">{selectedItem.category}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Status:</span>
                          <div className="font-medium text-gray-900">{selectedItem.status}</div>
                        </div>
                      </div>
                    </div>
                    <div>
                      <h4 className="font-semibold text-gray-700 mb-3">Archive Details</h4>
                      <div className="space-y-3">
                        <div>
                          <span className="text-sm text-gray-500">Archived By:</span>
                          <div className="font-medium text-gray-900">{selectedItem.archivedBy}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Date Archived:</span>
                          <div className="font-medium text-gray-900">{selectedItem.archivedDate}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Time Archived:</span>
                          <div className="font-medium text-gray-900">{selectedItem.archivedTime}</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  {selectedItem.description && (
                    <div>
                      <h4 className="font-semibold text-gray-700 mb-3">Description</h4>
                      <div className="p-3 bg-gray-50 rounded-lg">
                        <p className="text-gray-700">{selectedItem.description}</p>
                      </div>
                    </div>
                  )}

                  {selectedItem.reason && (
                    <div>
                      <h4 className="font-semibold text-gray-700 mb-3">Reason for Archiving</h4>
                      <div className="p-3 bg-gray-50 rounded-lg">
                        <p className="text-gray-700">{selectedItem.reason}</p>
                      </div>
                    </div>
                  )}
                </div>
              )}
            </div>
            <div className="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
              <button 
                onClick={() => handleRestore(selectedItem?.id)}
                className="flex items-center gap-2 px-4 py-2 text-green-600 hover:text-green-900"
              >
                <FaUndo className="h-4 w-4" />
                Restore
              </button>
              <button 
                onClick={() => setShowModal(false)}
                className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}

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

export default Archive; 