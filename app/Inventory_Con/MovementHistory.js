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
  FaMapMarkerAlt, 
  FaTruck, 
  FaBox, 
  FaUser, 
  FaRedo 
} from "react-icons/fa";
import { Package, Truck, CheckCircle, AlertCircle, Clock, ArrowRight } from "lucide-react";

const MovementHistory = () => {
  const [movements, setMovements] = useState([]);
  const [filteredMovements, setFilteredMovements] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedType, setSelectedType] = useState("all");
  const [selectedLocation, setSelectedLocation] = useState("all");
  const [selectedDateRange, setSelectedDateRange] = useState("all");
  const [page, setPage] = useState(1);
  const [rowsPerPage] = useState(10);
  const [selectedMovement, setSelectedMovement] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [locations, setLocations] = useState([]);
  const [showModal, setShowModal] = useState(false);

  // API call function
  const handleApiCall = async (action, data = {}) => {
    try {
      const response = await fetch('http://localhost/Enguio_Project/Api/backend.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: action,
          ...data
        }),
      });

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      const responseText = await response.text();
      
      // Check if response is valid JSON
      let result;
      try {
        result = JSON.parse(responseText);
      } catch (jsonError) {
        console.error('Invalid JSON response:', responseText);
        throw new Error('Server returned invalid JSON. Please check the server logs.');
      }
      
      if (!result.success) {
        throw new Error(result.message || 'API call failed');
      }
      
      return result;
    } catch (error) {
      console.error('API Error:', error);
      toast.error(error.message || 'Failed to fetch data');
      throw error;
    }
  };

  // Fetch movement history data
  const fetchMovementHistory = async () => {
    setIsLoading(true);
    try {
      const filters = {
        search: searchTerm,
        movement_type: selectedType,
        location: selectedLocation,
        date_range: selectedDateRange
      };
      
      const result = await handleApiCall('get_movement_history', filters);
      setMovements(result.data || []);
      setFilteredMovements(result.data || []);
    } catch (error) {
      console.error('Failed to fetch movement history:', error);
      setMovements([]);
      setFilteredMovements([]);
    } finally {
      setIsLoading(false);
    }
  };

  // Fetch locations for filter
  const fetchLocations = async () => {
    try {
      const result = await handleApiCall('get_locations_for_filter');
      setLocations(result.data || []);
    } catch (error) {
      console.error('Failed to fetch locations:', error);
      setLocations([]);
    }
  };

  // Initial data fetch
  useEffect(() => {
    fetchMovementHistory();
    fetchLocations();
  }, []);

  // Refetch data when filters change
  useEffect(() => {
    const timeoutId = setTimeout(() => {
      fetchMovementHistory();
    }, 500); // Debounce search

    return () => clearTimeout(timeoutId);
  }, [searchTerm, selectedType, selectedLocation, selectedDateRange]);

  const getStatusColor = (status) => {
    switch (status) {
      case "Completed":
        return "bg-green-100 text-green-800";
      case "In Progress":
      case "Pending":
        return "bg-yellow-100 text-yellow-800";
      case "Cancelled":
        return "bg-red-100 text-red-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  };

  const getTypeColor = (type) => {
    switch (type) {
      case "Transfer":
        return "bg-blue-100 text-blue-800";
      case "Receipt":
        return "bg-green-100 text-green-800";
      case "Return":
        return "bg-yellow-100 text-yellow-800";
      case "Adjustment":
        return "bg-purple-100 text-purple-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  };

  const handleViewDetails = (movement) => {
    setSelectedMovement(movement);
    setShowModal(true);
  };

  const handleRefresh = () => {
    fetchMovementHistory();
    toast.success('Movement history refreshed');
  };

  const handleExport = () => {
    // TODO: Implement export functionality
    toast.info('Export functionality coming soon');
  };

  const movementTypes = ["all", "Transfer"]; // Only Transfer for now since that's what we have
  const dateRanges = ["all", "today", "week", "month"];

  const pages = Math.ceil(filteredMovements.length / rowsPerPage);
  const items = filteredMovements.slice((page - 1) * rowsPerPage, page * rowsPerPage);

  const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const formatTime = (timeString) => {
    if (!timeString) return '';
    return timeString;
  };

  // Calculate statistics
  const totalMovements = filteredMovements.length;
  const completedMovements = filteredMovements.filter(m => m.status === 'Completed').length;
  const pendingMovements = filteredMovements.filter(m => m.status === 'Pending' || m.status === 'In Progress').length;
  const totalQuantity = filteredMovements.reduce((sum, m) => sum + Math.abs(Number(m.quantity) || 0), 0);

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Movement History</h1>
          <p className="text-gray-600">Track all inventory movements and transfers</p>
        </div>
        <div className="flex gap-3">
          <button 
            onClick={handleRefresh}
            disabled={isLoading}
            className="flex items-center gap-2 px-4 py-2 text-blue-600 hover:text-blue-900 disabled:opacity-50"
          >
            <FaRedo className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`} />
            Refresh
          </button>
          <button 
            onClick={handleExport}
            className="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
          >
            <FaDownload className="h-4 w-4" />
            Export Report
          </button>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <Truck className="h-8 w-8 text-blue-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Movements</p>
              <p className="text-2xl font-bold text-gray-900">{totalMovements}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <CheckCircle className="h-8 w-8 text-green-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Completed</p>
              <p className="text-2xl font-bold text-gray-900">{completedMovements}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <Clock className="h-8 w-8 text-yellow-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Pending</p>
              <p className="text-2xl font-bold text-gray-900">{pendingMovements}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <Package className="h-8 w-8 text-purple-500" />
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
                placeholder="Search movements..."
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
              {movementTypes.map((type) => (
                <option key={type} value={type}>
                  {type === "all" ? "All Types" : type}
                </option>
              ))}
            </select>
          </div>
          <div>
            <select
              value={selectedLocation}
              onChange={(e) => setSelectedLocation(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="all">All Locations</option>
              {locations.map((location) => (
                <option key={location.location_name} value={location.location_name}>
                  {location.location_name}
                </option>
              ))}
            </select>
          </div>
        </div>
        <div className="mt-4">
          <select
            value={selectedDateRange}
            onChange={(e) => setSelectedDateRange(e.target.value)}
            className="w-full md:w-48 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
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

      {/* Movement History Table */}
      <div className="bg-white rounded-3xl shadow-xl">
        <div className="px-6 py-4 border-b border-gray-200">
          <div className="flex justify-between items-center">
            <h3 className="text-xl font-semibold text-gray-900">Movement Records</h3>
            <div className="text-sm text-gray-500">
              {isLoading ? (
                <div className="flex items-center gap-2">
                  <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div>
                  Loading...
                </div>
              ) : (
                `${filteredMovements.length} movements found`
              )}
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
                  FROM
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  TO
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  MOVED BY
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
              {isLoading ? (
                <tr>
                  <td colSpan={9} className="px-6 py-4 text-center text-gray-500">
                    Loading movements...
                  </td>
                </tr>
              ) : items.length > 0 ? (
                items.map((item) => (
                  <tr key={`${item.id}-${item.productId}`} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <div>
                        <div className="text-sm font-medium text-gray-900">{item.product_name}</div>
                        <div className="text-sm text-gray-500">ID: {item.productId}</div>
                        <div className="text-xs text-gray-400">{item.category}</div>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <span className={`inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full ${getTypeColor(item.movementType)}`}>
                        <FaTruck className="h-3 w-3" />
                        {item.movementType}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-center">
                      <div className={`font-semibold ${item.quantity < 0 ? 'text-red-500' : 'text-green-500'}`}>
                        {item.quantity > 0 ? '+' : ''}{item.quantity}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-2">
                        <FaMapMarkerAlt className="text-gray-400 h-3 w-3" />
                        <span className="text-sm text-gray-900">{item.fromLocation}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-center">
                      <div className="flex items-center justify-center gap-2">
                        <FaMapMarkerAlt className="text-gray-400 h-3 w-3" />
                        <span className="text-sm text-gray-900">{item.toLocation}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-2">
                        <FaUser className="text-gray-400 h-3 w-3" />
                        <span className="text-sm text-gray-900">{item.movedBy}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div>
                        <div className="text-sm font-medium text-gray-900">{formatDate(item.date)}</div>
                        <div className="text-sm text-gray-500">{formatTime(item.time)}</div>
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
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={9} className="px-6 py-8 text-center">
                    <div className="flex flex-col items-center space-y-3">
                      <FaBox className="h-12 w-12 text-gray-300" />
                      <div className="text-gray-500">
                        <p className="text-lg font-medium">No movement records found</p>
                        <p className="text-sm">Try adjusting your filters or refresh the data</p>
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

      {/* Details Modal */}
      {showModal && (
        <div className="fixed inset-0 backdrop-blur-md flex items-center justify-center z-50">
          <div className="bg-white rounded-3xl shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div className="px-6 py-4 border-b border-gray-200">
              <div className="flex justify-between items-center">
                <h3 className="text-xl font-semibold text-gray-900">Movement Details</h3>
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
              {selectedMovement && (
                <div className="space-y-6">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <h4 className="font-semibold text-gray-700 mb-3">Product Information</h4>
                      <div className="space-y-3">
                        <div>
                          <span className="text-sm text-gray-500">Product Name:</span>
                          <div className="font-medium text-gray-900">{selectedMovement.product_name}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Product ID:</span>
                          <div className="font-medium text-gray-900">{selectedMovement.productId}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Category:</span>
                          <div className="font-medium text-gray-900">{selectedMovement.category}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Brand:</span>
                          <div className="font-medium text-gray-900">{selectedMovement.brand || 'N/A'}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Reference:</span>
                          <div className="font-medium text-gray-900">{selectedMovement.reference}</div>
                        </div>
                      </div>
                    </div>
                    <div>
                      <h4 className="font-semibold text-gray-700 mb-3">Movement Details</h4>
                      <div className="space-y-3">
                        <div>
                          <span className="text-sm text-gray-500">Type:</span>
                          <div className="font-medium text-gray-900">{selectedMovement.movementType}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Quantity:</span>
                          <div className={`font-medium ${selectedMovement.quantity < 0 ? 'text-red-500' : 'text-green-500'}`}>
                            {selectedMovement.quantity > 0 ? '+' : ''}{selectedMovement.quantity}
                          </div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Status:</span>
                          <div className="font-medium text-gray-900">{selectedMovement.status}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Unit Price:</span>
                          <div className="font-medium text-gray-900">₱{selectedMovement.unit_price?.toFixed(2) || 'N/A'}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <h4 className="font-semibold text-gray-700 mb-3">From Location</h4>
                      <div className="flex items-center gap-2">
                        <FaMapMarkerAlt className="text-gray-400" />
                        <span className="font-medium text-gray-900">{selectedMovement.fromLocation}</span>
                      </div>
                    </div>
                    <div>
                      <h4 className="font-semibold text-gray-700 mb-3">To Location</h4>
                      <div className="flex items-center gap-2">
                        <FaMapMarkerAlt className="text-gray-400" />
                        <span className="font-medium text-gray-900">{selectedMovement.toLocation}</span>
                      </div>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <h4 className="font-semibold text-gray-700 mb-3">Moved By</h4>
                      <div className="flex items-center gap-2">
                        <FaUser className="text-gray-400" />
                        <span className="font-medium text-gray-900">{selectedMovement.movedBy}</span>
                      </div>
                    </div>
                    <div>
                      <h4 className="font-semibold text-gray-700 mb-3">Date & Time</h4>
                      <div className="flex items-center gap-2">
                        <FaCalendar className="text-gray-400" />
                        <span className="font-medium text-gray-900">{formatDate(selectedMovement.date)} at {formatTime(selectedMovement.time)}</span>
                      </div>
                    </div>
                  </div>

                  {selectedMovement.description && (
                    <div>
                      <h4 className="font-semibold text-gray-700 mb-3">Description</h4>
                      <div className="p-3 bg-gray-50 rounded-lg">
                        <p className="text-gray-700">{selectedMovement.description}</p>
                      </div>
                    </div>
                  )}

                  {selectedMovement.notes && selectedMovement.notes !== null && (
                    <div>
                      <h4 className="font-semibold text-gray-700 mb-3">Notes</h4>
                      <div className="p-3 bg-gray-50 rounded-lg">
                        <p className="text-gray-700">{selectedMovement.notes}</p>
                      </div>
                    </div>
                  )}
                </div>
              )}
            </div>
            <div className="px-6 py-4 border-t border-gray-200 flex justify-end">
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

export default MovementHistory; 