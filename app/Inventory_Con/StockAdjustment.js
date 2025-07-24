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
  FaMinus, 
  FaPlus as FaPlusIcon 
} from "react-icons/fa";
import { Package, TrendingUp, TrendingDown, CheckCircle, Clock, AlertCircle } from "lucide-react";

const StockAdjustment = () => {
  const [adjustments, setAdjustments] = useState([]);
  const [filteredAdjustments, setFilteredAdjustments] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedType, setSelectedType] = useState("all");
  const [selectedStatus, setSelectedStatus] = useState("all");
  const [page, setPage] = useState(1);
  const [rowsPerPage] = useState(10);
  const [editingAdjustment, setEditingAdjustment] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [showModal, setShowModal] = useState(false);

  // Sample data - replace with actual API calls
  const sampleData = [
    {
      id: 1,
      productName: "Paracetamol 500mg",
      productId: "MED001",
      adjustmentType: "Addition",
      quantity: 50,
      reason: "Stock replenishment",
      adjustedBy: "John Doe",
      date: "2024-01-15",
      time: "10:30 AM",
      status: "Approved",
      notes: "Regular stock addition"
    },
    {
      id: 2,
      productName: "Amoxicillin 250mg",
      productId: "MED002",
      adjustmentType: "Subtraction",
      quantity: 25,
      reason: "Damaged goods",
      adjustedBy: "Jane Smith",
      date: "2024-01-14",
      time: "02:15 PM",
      status: "Pending",
      notes: "Found damaged packaging"
    },
    {
      id: 3,
      productName: "Vitamin C 1000mg",
      productId: "MED003",
      adjustmentType: "Addition",
      quantity: 100,
      reason: "New shipment",
      adjustedBy: "Mike Johnson",
      date: "2024-01-13",
      time: "09:45 AM",
      status: "Approved",
      notes: "Fresh stock from supplier"
    },
    {
      id: 4,
      productName: "Omeprazole 20mg",
      productId: "MED004",
      adjustmentType: "Subtraction",
      quantity: 10,
      reason: "Expired products",
      adjustedBy: "Sarah Wilson",
      date: "2024-01-12",
      time: "04:20 PM",
      status: "Approved",
      notes: "Removed expired items"
    },
    {
      id: 5,
      productName: "Ibuprofen 400mg",
      productId: "MED005",
      adjustmentType: "Addition",
      quantity: 75,
      reason: "Return from customer",
      adjustedBy: "David Brown",
      date: "2024-01-11",
      time: "11:30 AM",
      status: "Pending",
      notes: "Customer returned unused medication"
    }
  ];

  useEffect(() => {
    setAdjustments(sampleData);
    setFilteredAdjustments(sampleData);
  }, []);

  useEffect(() => {
    filterAdjustments();
  }, [searchTerm, selectedType, selectedStatus, adjustments]);

  const filterAdjustments = () => {
    let filtered = adjustments;

    if (searchTerm) {
      filtered = filtered.filter(item =>
        item.productName.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.productId.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.adjustedBy.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.reason.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    if (selectedType !== "all") {
      filtered = filtered.filter(item => item.adjustmentType === selectedType);
    }

    if (selectedStatus !== "all") {
      filtered = filtered.filter(item => item.status === selectedStatus);
    }

    setFilteredAdjustments(filtered);
  };

  const getStatusColor = (status) => {
    switch (status) {
      case "Approved":
        return "bg-green-100 text-green-800";
      case "Pending":
        return "bg-yellow-100 text-yellow-800";
      case "Rejected":
        return "bg-red-100 text-red-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  };

  const getTypeColor = (type) => {
    switch (type) {
      case "Addition":
        return "bg-green-100 text-green-800";
      case "Subtraction":
        return "bg-red-100 text-red-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  };

  const handleEdit = (adjustment) => {
    setEditingAdjustment(adjustment);
    setShowModal(true);
  };

  const handleSave = () => {
    if (editingAdjustment) {
      setAdjustments(prev => 
        prev.map(item => 
          item.id === editingAdjustment.id ? editingAdjustment : item
        )
      );
      toast.success('Adjustment updated successfully');
    }
    setShowModal(false);
    setEditingAdjustment(null);
  };

  const handleDelete = (id) => {
    setAdjustments(prev => prev.filter(item => item.id !== id));
    toast.success('Adjustment deleted successfully');
  };

  const adjustmentTypes = ["all", "Addition", "Subtraction"];
  const statuses = ["all", "Approved", "Pending", "Rejected"];

  const pages = Math.ceil(filteredAdjustments.length / rowsPerPage);
  const items = filteredAdjustments.slice((page - 1) * rowsPerPage, page * rowsPerPage);

  // Calculate statistics
  const totalAdjustments = filteredAdjustments.length;
  const approvedAdjustments = filteredAdjustments.filter(a => a.status === 'Approved').length;
  const pendingAdjustments = filteredAdjustments.filter(a => a.status === 'Pending').length;
  const totalQuantity = filteredAdjustments.reduce((sum, a) => sum + Number(a.quantity), 0);

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Stock Adjustment</h1>
          <p className="text-gray-600">Manage inventory adjustments and stock modifications</p>
        </div>
        <div className="flex gap-3">
          <button className="flex items-center gap-2 px-4 py-2 text-blue-600 hover:text-blue-900">
            <FaUpload className="h-4 w-4" />
            Import
          </button>
          <button className="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
            <FaDownload className="h-4 w-4" />
            Export
          </button>
          <button className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            <FaPlus className="h-4 w-4" />
            New Adjustment
          </button>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <Package className="h-8 w-8 text-blue-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Adjustments</p>
              <p className="text-2xl font-bold text-gray-900">{totalAdjustments}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <CheckCircle className="h-8 w-8 text-green-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Approved</p>
              <p className="text-2xl font-bold text-gray-900">{approvedAdjustments}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <Clock className="h-8 w-8 text-yellow-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Pending</p>
              <p className="text-2xl font-bold text-gray-900">{pendingAdjustments}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <TrendingUp className="h-8 w-8 text-purple-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Quantity</p>
              <p className="text-2xl font-bold text-gray-900">{totalQuantity}</p>
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
                placeholder="Search adjustments..."
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
              {adjustmentTypes.map((type) => (
                <option key={type} value={type}>
                  {type === "all" ? "All Types" : type}
                </option>
              ))}
            </select>
          </div>
          <div>
            <select
              value={selectedStatus}
              onChange={(e) => setSelectedStatus(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              {statuses.map((status) => (
                <option key={status} value={status}>
                  {status === "all" ? "All Status" : status}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Adjustments Table */}
      <div className="bg-white rounded-3xl shadow-xl">
        <div className="px-6 py-4 border-b border-gray-200">
          <div className="flex justify-between items-center">
            <h3 className="text-xl font-semibold text-gray-900">Adjustments</h3>
            <div className="text-sm text-gray-500">
              {filteredAdjustments.length} adjustments found
            </div>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  PRODUCT
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  TYPE
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  QUANTITY
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  REASON
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  ADJUSTED BY
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  DATE & TIME
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
              {items.map((item) => (
                <tr key={item.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4">
                    <div>
                      <div className="text-sm font-medium text-gray-900">{item.productName}</div>
                      <div className="text-sm text-gray-500">ID: {item.productId}</div>
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <span className={`inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full ${getTypeColor(item.adjustmentType)}`}>
                      {item.adjustmentType === "Addition" ? <FaPlusIcon className="h-3 w-3" /> : <FaMinus className="h-3 w-3" />}
                      {item.adjustmentType}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-center">
                    <div className="font-semibold">{item.quantity}</div>
                  </td>
                  <td className="px-6 py-4">
                    <div className="max-w-xs truncate" title={item.reason}>
                      <span className="text-sm text-gray-900">{item.reason}</span>
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <span className="text-sm text-gray-900">{item.adjustedBy}</span>
                  </td>
                  <td className="px-6 py-4">
                    <div>
                      <div className="text-sm font-medium text-gray-900">{item.date}</div>
                      <div className="text-sm text-gray-500">{item.time}</div>
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
                        onClick={() => handleEdit(item)}
                        className="text-blue-600 hover:text-blue-900 p-1"
                      >
                        <FaEdit className="h-4 w-4" />
                      </button>
                      <button className="text-green-600 hover:text-green-900 p-1">
                        <FaEye className="h-4 w-4" />
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
              ))}
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

      {/* Edit Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-3xl shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div className="px-6 py-4 border-b border-gray-200">
              <div className="flex justify-between items-center">
                <h3 className="text-xl font-semibold text-gray-900">Edit Adjustment</h3>
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
              {editingAdjustment && (
                <div className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                      <input
                        type="text"
                        value={editingAdjustment.productName}
                        onChange={(e) => setEditingAdjustment({...editingAdjustment, productName: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Product ID</label>
                      <input
                        type="text"
                        value={editingAdjustment.productId}
                        onChange={(e) => setEditingAdjustment({...editingAdjustment, productId: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Adjustment Type</label>
                      <select
                        value={editingAdjustment.adjustmentType}
                        onChange={(e) => setEditingAdjustment({...editingAdjustment, adjustmentType: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        {adjustmentTypes.filter(type => type !== "all").map((type) => (
                          <option key={type} value={type}>
                            {type}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                      <input
                        type="number"
                        value={editingAdjustment.quantity}
                        onChange={(e) => setEditingAdjustment({...editingAdjustment, quantity: parseInt(e.target.value)})}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                    <input
                      type="text"
                      value={editingAdjustment.reason}
                      onChange={(e) => setEditingAdjustment({...editingAdjustment, reason: e.target.value})}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Adjusted By</label>
                      <input
                        type="text"
                        value={editingAdjustment.adjustedBy}
                        onChange={(e) => setEditingAdjustment({...editingAdjustment, adjustedBy: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                      <select
                        value={editingAdjustment.status}
                        onChange={(e) => setEditingAdjustment({...editingAdjustment, status: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        {statuses.filter(status => status !== "all").map((status) => (
                          <option key={status} value={status}>
                            {status}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea
                      value={editingAdjustment.notes}
                      onChange={(e) => setEditingAdjustment({...editingAdjustment, notes: e.target.value})}
                      placeholder="Additional notes..."
                      rows={3}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                </div>
              )}
            </div>
            <div className="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
              <button 
                onClick={() => setShowModal(false)}
                className="px-4 py-2 text-gray-600 hover:text-gray-800"
              >
                Cancel
              </button>
              <button 
                onClick={handleSave}
                className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
              >
                Save Changes
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

export default StockAdjustment; 