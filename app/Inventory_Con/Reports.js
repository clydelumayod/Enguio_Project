"use client";
import React, { useState, useEffect } from "react";
import { toast, ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import { 
  FaDownload, 
  FaPrint, 
  FaChartBar, 
  FaChartLine, 
  FaChartPie, 
  FaCalendar, 
  FaFilter, 
  FaEye, 
  FaFileAlt 
} from "react-icons/fa";
import { BarChart3, TrendingUp, PieChart, FileText, CheckCircle, Clock, AlertCircle } from "lucide-react";

const Reports = () => {
  const [reports, setReports] = useState([]);
  const [filteredReports, setFilteredReports] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedType, setSelectedType] = useState("all");
  const [selectedDateRange, setSelectedDateRange] = useState("all");
  const [page, setPage] = useState(1);
  const [rowsPerPage] = useState(10);
  const [selectedReport, setSelectedReport] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [showModal, setShowModal] = useState(false);
  const [analyticsData, setAnalyticsData] = useState({
    totalProducts: 0,
    lowStockItems: 0,
    outOfStockItems: 0,
    totalValue: 0
  });
  const [topCategories, setTopCategories] = useState([]);

  // Fetch data from database
  useEffect(() => {
    fetchReportsData();
  }, []);

  const fetchReportsData = async () => {
    setIsLoading(true);
    try {
      const API_BASE_URL = "http://localhost/Enguio_Project/Api/backend.php";
      
      const response = await fetch(API_BASE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_reports_data' })
      });
      
      const data = await response.json();
      
      if (data.success) {
        setReports(data.reports || []);
        setFilteredReports(data.reports || []);
        setAnalyticsData(data.analytics || {
          totalProducts: 0,
          lowStockItems: 0,
          outOfStockItems: 0,
          totalValue: 0
        });
        setTopCategories(data.topCategories || []);
      } else {
        toast.error('Failed to fetch reports data: ' + data.message);
      }
    } catch (error) {
      console.error('Error fetching reports data:', error);
      toast.error('Error connecting to server');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    filterReports();
  }, [searchTerm, selectedType, selectedDateRange, reports]);

  const filterReports = () => {
    let filtered = reports;

    if (searchTerm) {
      filtered = filtered.filter(item =>
        item.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.generatedBy.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.description.toLowerCase().includes(searchTerm.toLowerCase())
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
          filtered = filtered.filter(item => item.date === today.toISOString().split('T')[0]);
          break;
        case "week":
          filteredDate.setDate(today.getDate() - 7);
          filtered = filtered.filter(item => new Date(item.date) >= filteredDate);
          break;
        case "month":
          filteredDate.setMonth(today.getMonth() - 1);
          filtered = filtered.filter(item => new Date(item.date) >= filteredDate);
          break;
        default:
          break;
      }
    }

    setFilteredReports(filtered);
  };

  const getStatusColor = (status) => {
    switch (status) {
      case "Completed":
        return "bg-green-100 text-green-800";
      case "In Progress":
        return "bg-yellow-100 text-yellow-800";
      case "Failed":
        return "bg-red-100 text-red-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  };

  const getTypeColor = (type) => {
    switch (type) {
      case "Stock In Report":
        return "bg-blue-100 text-blue-800";
      case "Stock Out Report":
        return "bg-red-100 text-red-800";
      case "Stock Adjustment Report":
        return "bg-yellow-100 text-yellow-800";
      case "Transfer Report":
        return "bg-purple-100 text-purple-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  };

  const handleViewDetails = (report) => {
    setSelectedReport(report);
    setShowModal(true);
  };

  const handleGenerateReport = async (reportType) => {
    setIsLoading(true);
    try {
      const API_BASE_URL = "http://localhost/Enguio_Project/Api/backend.php";
      let action = '';
      let requestData = {};
      
      switch (reportType) {
        case 'inventory_summary':
          action = 'get_inventory_summary_report';
          break;
        case 'low_stock':
          action = 'get_low_stock_report';
          requestData = { threshold: 10 };
          break;
        case 'expiry':
          action = 'get_expiry_report';
          requestData = { days_threshold: 30 };
          break;
        case 'movement_history':
          action = 'get_movement_history_report';
          requestData = { 
            start_date: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            end_date: new Date().toISOString().split('T')[0]
          };
          break;
        default:
          toast.info('Report generation feature coming soon');
          setIsLoading(false);
          return;
      }

      const response = await fetch(API_BASE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, ...requestData })
      });
      
      const data = await response.json();
      
      if (data.success) {
        toast.success(`${reportType.replace('_', ' ')} report generated successfully`);
        // Here you could trigger download or show the report data
        console.log('Report data:', data.data);
      } else {
        toast.error('Failed to generate report: ' + data.message);
      }
    } catch (error) {
      console.error('Error generating report:', error);
      toast.error('Error generating report');
    } finally {
      setIsLoading(false);
    }
  };

  const handleDownload = (report) => {
    toast.success(`Downloading ${report.title}`);
  };

  const handlePrint = (report) => {
    toast.info(`Printing ${report.title}`);
  };

  const reportTypes = ["all", "Stock In Report", "Stock Out Report", "Stock Adjustment Report", "Transfer Report"];
  const dateRanges = ["all", "today", "week", "month"];

  const pages = Math.ceil(filteredReports.length / rowsPerPage);
  const items = filteredReports.slice((page - 1) * rowsPerPage, page * rowsPerPage);

  // Calculate statistics
  const totalReports = filteredReports.length;
  const completedReports = filteredReports.filter(r => r.status === 'Completed').length;
  const inProgressReports = filteredReports.filter(r => r.status === 'In Progress').length;
  const totalFileSize = filteredReports.reduce((sum, r) => {
    const size = parseFloat(r.fileSize.replace(' MB', ''));
    return sum + size;
  }, 0);

  // Generate colors for categories
  const categoryColors = [
    "bg-green-500", "bg-blue-500", "bg-yellow-500", 
    "bg-purple-500", "bg-red-500", "bg-indigo-500"
  ];

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Reports</h1>
          <p className="text-gray-600">Generate and manage inventory reports and analytics</p>
        </div>
        <div className="flex gap-3">
          <button 
            onClick={() => handleGenerateReport('inventory_summary')}
            disabled={isLoading}
            className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            <FaChartBar className="h-4 w-4" />
            {isLoading ? 'Generating...' : 'Generate Report'}
          </button>
        </div>
      </div>

      {/* Analytics Overview */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <BarChart3 className="h-8 w-8 text-blue-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Products</p>
              <p className="text-2xl font-bold text-gray-900">{analyticsData.totalProducts?.toLocaleString() || 0}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <AlertCircle className="h-8 w-8 text-yellow-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Low Stock Items</p>
              <p className="text-2xl font-bold text-gray-900">{analyticsData.lowStockItems || 0}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <PieChart className="h-8 w-8 text-red-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Out of Stock</p>
              <p className="text-2xl font-bold text-gray-900">{analyticsData.outOfStockItems || 0}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <FileText className="h-8 w-8 text-green-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Value</p>
              <p className="text-2xl font-bold text-gray-900">₱{((analyticsData.totalValue || 0) / 1000000).toFixed(1)}M</p>
            </div>
          </div>
        </div>
      </div>

      {/* Report Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <FileText className="h-8 w-8 text-blue-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Reports</p>
              <p className="text-2xl font-bold text-gray-900">{totalReports}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <CheckCircle className="h-8 w-8 text-green-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Completed</p>
              <p className="text-2xl font-bold text-gray-900">{completedReports}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <Clock className="h-8 w-8 text-yellow-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">In Progress</p>
              <p className="text-2xl font-bold text-gray-900">{inProgressReports}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center">
            <TrendingUp className="h-8 w-8 text-purple-500" />
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Size</p>
              <p className="text-2xl font-bold text-gray-900">{totalFileSize.toFixed(1)} MB</p>
            </div>
          </div>
        </div>
      </div>

      {/* Category Distribution */}
      {topCategories.length > 0 && (
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center gap-3 mb-6">
            <PieChart className="h-6 w-6 text-blue-500" />
            <h3 className="text-xl font-semibold text-gray-900">Top Categories Distribution</h3>
          </div>
          <div className="space-y-4">
            {topCategories.map((category, index) => (
              <div key={index} className="flex items-center gap-4">
                <div className="w-32">
                  <span className="text-sm font-medium text-gray-900">{category.category_name}</span>
                </div>
                <div className="flex-1">
                  <div className="w-full bg-gray-200 rounded-full h-2">
                    <div 
                      className={`h-2 rounded-full ${categoryColors[index % categoryColors.length]}`}
                      style={{ width: `${category.percentage}%` }}
                    ></div>
                  </div>
                </div>
                <div className="w-16 text-right">
                  <span className="text-sm font-medium text-gray-900">{category.percentage}%</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Filters and Search */}
      <div className="bg-white rounded-3xl shadow-xl p-6">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="md:col-span-2">
            <div className="relative">
              <FaFilter className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <input
                type="text"
                placeholder="Search reports..."
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
              {reportTypes.map((type) => (
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

      {/* Reports Table */}
      <div className="bg-white rounded-3xl shadow-xl">
        <div className="px-6 py-4 border-b border-gray-200">
          <div className="flex justify-between items-center">
            <h3 className="text-xl font-semibold text-gray-900">Generated Reports</h3>
            <div className="text-sm text-gray-500">
              {filteredReports.length} reports found
            </div>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  REPORT TITLE
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  TYPE
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  GENERATED BY
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  DATE & TIME
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  STATUS
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  FILE INFO
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  ACTIONS
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {items.map((item) => (
                <tr key={item.movement_id} className="hover:bg-gray-50">
                  <td className="px-6 py-4">
                    <div>
                      <div className="text-sm font-medium text-gray-900">{item.title}</div>
                      <div className="text-sm text-gray-500 max-w-xs truncate">{item.description}</div>
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <span className={`inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full ${getTypeColor(item.type)}`}>
                      <FaFileAlt className="h-3 w-3" />
                      {item.type}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <span className="text-sm text-gray-900">{item.generatedBy}</span>
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
                  <td className="px-6 py-4">
                    <div>
                      <div className="text-sm font-medium text-gray-900">{item.format}</div>
                      <div className="text-sm text-gray-500">{item.fileSize}</div>
                    </div>
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
                        onClick={() => handleDownload(item)}
                        className="text-green-600 hover:text-green-900 p-1"
                      >
                        <FaDownload className="h-4 w-4" />
                      </button>
                      <button 
                        onClick={() => handlePrint(item)}
                        className="text-purple-600 hover:text-purple-900 p-1"
                      >
                        <FaPrint className="h-4 w-4" />
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

      {/* Report Details Modal */}
      {showModal && (
        <div className="fixed inset-0 backdrop-blur-md flex items-center justify-center z-50">
          <div className="bg-white rounded-3xl shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div className="px-6 py-4 border-b border-gray-200">
              <div className="flex justify-between items-center">
                <h3 className="text-xl font-semibold text-gray-900">Report Details</h3>
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
              {selectedReport && (
                <div className="space-y-6">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <h4 className="font-semibold text-gray-700 mb-3">Report Information</h4>
                      <div className="space-y-3">
                        <div>
                          <span className="text-sm text-gray-500">Title:</span>
                          <div className="font-medium text-gray-900">{selectedReport.title}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Type:</span>
                          <div className="font-medium text-gray-900">{selectedReport.type}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Status:</span>
                          <div className="font-medium text-gray-900">{selectedReport.status}</div>
                        </div>
                      </div>
                    </div>
                    <div>
                      <h4 className="font-semibold text-gray-700 mb-3">File Details</h4>
                      <div className="space-y-3">
                        <div>
                          <span className="text-sm text-gray-500">Format:</span>
                          <div className="font-medium text-gray-900">{selectedReport.format}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">File Size:</span>
                          <div className="font-medium text-gray-900">{selectedReport.fileSize}</div>
                        </div>
                        <div>
                          <span className="text-sm text-gray-500">Generated By:</span>
                          <div className="font-medium text-gray-900">{selectedReport.generatedBy}</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div>
                    <h4 className="font-semibold text-gray-700 mb-3">Description</h4>
                    <div className="p-3 bg-gray-50 rounded-lg">
                      <p className="text-gray-700">{selectedReport.description}</p>
                    </div>
                  </div>

                  <div>
                    <h4 className="font-semibold text-gray-700 mb-3">Generated On</h4>
                    <div className="flex items-center gap-2">
                      <FaCalendar className="text-gray-400" />
                      <span className="font-medium text-gray-900">{selectedReport.date} at {selectedReport.time}</span>
                    </div>
                  </div>
                </div>
              )}
            </div>
            <div className="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
              <button 
                onClick={() => handleDownload(selectedReport)}
                className="flex items-center gap-2 px-4 py-2 text-blue-600 hover:text-blue-900"
              >
                <FaDownload className="h-4 w-4" />
                Download
              </button>
              <button 
                onClick={() => handlePrint(selectedReport)}
                className="flex items-center gap-2 px-4 py-2 text-purple-600 hover:text-purple-900"
              >
                <FaPrint className="h-4 w-4" />
                Print
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

export default Reports; 