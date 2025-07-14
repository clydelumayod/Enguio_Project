"use client";
import React, { useState, useEffect } from "react";
import { Card, CardBody, CardHeader, Button, Input, Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Chip, Pagination, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, useDisclosure, Select, SelectItem, Progress } from "@nextui-org/react";
import { FaDownload, FaPrint, FaChartBar, FaChartLine, FaChartPie, FaCalendar, FaFilter, FaEye, FaFileAlt } from "react-icons/fa";

const Reports = () => {
  const [reports, setReports] = useState([]);
  const [filteredReports, setFilteredReports] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedType, setSelectedType] = useState("all");
  const [selectedDateRange, setSelectedDateRange] = useState("all");
  const [page, setPage] = useState(1);
  const [rowsPerPage] = useState(10);
  const { isOpen, onOpen, onClose } = useDisclosure();
  const [selectedReport, setSelectedReport] = useState(null);
  const [isLoading, setIsLoading] = useState(false);

  // Sample data - replace with actual API calls
  const sampleData = [
    {
      id: 1,
      title: "Monthly Inventory Summary",
      type: "Summary Report",
      generatedBy: "John Doe",
      date: "2024-01-15",
      time: "10:30 AM",
      status: "Completed",
      fileSize: "2.5 MB",
      format: "PDF",
      description: "Comprehensive monthly inventory summary with stock levels and movements"
    },
    {
      id: 2,
      title: "Low Stock Alert Report",
      type: "Alert Report",
      generatedBy: "Jane Smith",
      date: "2024-01-14",
      time: "02:15 PM",
      status: "Completed",
      fileSize: "1.2 MB",
      format: "Excel",
      description: "Products with stock levels below minimum threshold"
    },
    {
      id: 3,
      title: "Movement Analysis Q4 2023",
      type: "Analytics Report",
      generatedBy: "Mike Johnson",
      date: "2024-01-13",
      time: "09:45 AM",
      status: "Completed",
      fileSize: "4.8 MB",
      format: "PDF",
      description: "Quarterly analysis of inventory movements and trends"
    },
    {
      id: 4,
      title: "Expiry Date Report",
      type: "Alert Report",
      generatedBy: "Sarah Wilson",
      date: "2024-01-12",
      time: "04:20 PM",
      status: "Completed",
      fileSize: "0.8 MB",
      format: "Excel",
      description: "Products approaching expiry dates"
    },
    {
      id: 5,
      title: "Supplier Performance Report",
      type: "Analytics Report",
      generatedBy: "David Brown",
      date: "2024-01-11",
      time: "11:30 AM",
      status: "In Progress",
      fileSize: "3.2 MB",
      format: "PDF",
      description: "Analysis of supplier delivery performance and quality"
    },
    {
      id: 6,
      title: "Daily Stock Count",
      type: "Summary Report",
      generatedBy: "Lisa Chen",
      date: "2024-01-10",
      time: "08:15 AM",
      status: "Completed",
      fileSize: "1.5 MB",
      format: "Excel",
      description: "Daily inventory count and reconciliation"
    }
  ];

  // Sample analytics data
  const analyticsData = {
    totalProducts: 1250,
    lowStockItems: 45,
    outOfStockItems: 12,
    totalValue: 1250000,
    monthlyGrowth: 8.5,
    topCategories: [
      { name: "Pain Relief", percentage: 25, color: "success" },
      { name: "Vitamins", percentage: 20, color: "primary" },
      { name: "Antibiotics", percentage: 15, color: "warning" },
      { name: "Gastrointestinal", percentage: 12, color: "secondary" },
      { name: "Others", percentage: 28, color: "default" }
    ]
  };

  useEffect(() => {
    setReports(sampleData);
    setFilteredReports(sampleData);
  }, []);

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
        return "success";
      case "In Progress":
        return "warning";
      case "Failed":
        return "danger";
      default:
        return "default";
    }
  };

  const getTypeColor = (type) => {
    switch (type) {
      case "Summary Report":
        return "primary";
      case "Alert Report":
        return "warning";
      case "Analytics Report":
        return "success";
      default:
        return "default";
    }
  };

  const handleViewDetails = (report) => {
    setSelectedReport(report);
    onOpen();
  };

  const reportTypes = ["all", "Summary Report", "Alert Report", "Analytics Report"];
  const dateRanges = ["all", "today", "week", "month"];

  const pages = Math.ceil(filteredReports.length / rowsPerPage);
  const items = filteredReports.slice((page - 1) * rowsPerPage, page * rowsPerPage);

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Reports</h1>
          <p className="text-gray-600">Generate and manage inventory reports and analytics</p>
        </div>
        <div className="flex gap-3">
          <Button color="primary" startContent={<FaChartBar />}>
            Generate Report
          </Button>
        </div>
      </div>

      {/* Analytics Overview */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardBody>
            <div className="flex items-center gap-3">
              <div className="p-3 bg-blue-100 rounded-lg">
                <FaChartBar className="text-blue-600 text-xl" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Total Products</p>
                <p className="text-2xl font-bold">{analyticsData.totalProducts.toLocaleString()}</p>
              </div>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <div className="flex items-center gap-3">
              <div className="p-3 bg-yellow-100 rounded-lg">
                <FaChartLine className="text-yellow-600 text-xl" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Low Stock Items</p>
                <p className="text-2xl font-bold text-yellow-600">{analyticsData.lowStockItems}</p>
              </div>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <div className="flex items-center gap-3">
              <div className="p-3 bg-red-100 rounded-lg">
                <FaChartPie className="text-red-600 text-xl" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Out of Stock</p>
                <p className="text-2xl font-bold text-red-600">{analyticsData.outOfStockItems}</p>
              </div>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <div className="flex items-center gap-3">
              <div className="p-3 bg-green-100 rounded-lg">
                <FaFileAlt className="text-green-600 text-xl" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Total Value</p>
                <p className="text-2xl font-bold text-green-600">₱{(analyticsData.totalValue / 1000000).toFixed(1)}M</p>
              </div>
            </div>
          </CardBody>
        </Card>
      </div>

      {/* Category Distribution */}
      <Card>
        <CardHeader>
          <h3 className="text-xl font-semibold">Top Categories Distribution</h3>
        </CardHeader>
        <CardBody>
          <div className="space-y-4">
            {analyticsData.topCategories.map((category, index) => (
              <div key={index} className="flex items-center gap-4">
                <div className="w-32">
                  <span className="text-sm font-medium">{category.name}</span>
                </div>
                <div className="flex-1">
                  <Progress 
                    value={category.percentage} 
                    color={category.color}
                    className="w-full"
                  />
                </div>
                <div className="w-16 text-right">
                  <span className="text-sm font-medium">{category.percentage}%</span>
                </div>
              </div>
            ))}
          </div>
        </CardBody>
      </Card>

      {/* Filters and Search */}
      <Card>
        <CardBody>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="md:col-span-2">
              <Input
                placeholder="Search reports..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                startContent={<FaFilter className="text-gray-400" />}
                className="w-full"
              />
            </div>
            <div>
              <Select
                placeholder="Report Type"
                selectedKeys={[selectedType]}
                onChange={(e) => setSelectedType(e.target.value)}
                startContent={<FaFileAlt className="text-gray-400" />}
              >
                {reportTypes.map((type) => (
                  <SelectItem key={type} value={type}>
                    {type === "all" ? "All Types" : type}
                  </SelectItem>
                ))}
              </Select>
            </div>
            <div>
              <Select
                placeholder="Date Range"
                selectedKeys={[selectedDateRange]}
                onChange={(e) => setSelectedDateRange(e.target.value)}
                startContent={<FaCalendar className="text-gray-400" />}
              >
                {dateRanges.map((range) => (
                  <SelectItem key={range} value={range}>
                    {range === "all" ? "All Time" : 
                     range === "today" ? "Today" :
                     range === "week" ? "Last 7 Days" :
                     range === "month" ? "Last 30 Days" : range}
                  </SelectItem>
                ))}
              </Select>
            </div>
          </div>
        </CardBody>
      </Card>

      {/* Reports Table */}
      <Card>
        <CardHeader>
          <div className="flex justify-between items-center">
            <h3 className="text-xl font-semibold">Generated Reports</h3>
            <div className="text-sm text-gray-500">
              {filteredReports.length} reports found
            </div>
          </div>
        </CardHeader>
        <CardBody>
          <Table aria-label="Reports table">
            <TableHeader>
              <TableColumn>REPORT TITLE</TableColumn>
              <TableColumn>TYPE</TableColumn>
              <TableColumn>GENERATED BY</TableColumn>
              <TableColumn>DATE & TIME</TableColumn>
              <TableColumn>STATUS</TableColumn>
              <TableColumn>FILE INFO</TableColumn>
              <TableColumn>ACTIONS</TableColumn>
            </TableHeader>
            <TableBody>
              {items.map((item) => (
                <TableRow key={item.id}>
                  <TableCell>
                    <div>
                      <div className="font-semibold">{item.title}</div>
                      <div className="text-sm text-gray-500 max-w-xs truncate">{item.description}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Chip 
                      color={getTypeColor(item.type)} 
                      variant="flat"
                      startContent={<FaFileAlt />}
                    >
                      {item.type}
                    </Chip>
                  </TableCell>
                  <TableCell>{item.generatedBy}</TableCell>
                  <TableCell>
                    <div>
                      <div className="font-semibold">{item.date}</div>
                      <div className="text-sm text-gray-500">{item.time}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Chip color={getStatusColor(item.status)} variant="flat">
                      {item.status}
                    </Chip>
                  </TableCell>
                  <TableCell>
                    <div>
                      <div className="font-semibold">{item.format}</div>
                      <div className="text-sm text-gray-500">{item.fileSize}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-2">
                      <Button isIconOnly size="sm" variant="light" onPress={() => handleViewDetails(item)}>
                        <FaEye className="text-blue-500" />
                      </Button>
                      <Button isIconOnly size="sm" variant="light">
                        <FaDownload className="text-green-500" />
                      </Button>
                      <Button isIconOnly size="sm" variant="light">
                        <FaPrint className="text-purple-500" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>

          {/* Pagination */}
          <div className="flex justify-center mt-4">
            <Pagination
              total={pages}
              page={page}
              onChange={setPage}
              showControls
              color="primary"
            />
          </div>
        </CardBody>
      </Card>

      {/* Report Details Modal */}
      <Modal isOpen={isOpen} onClose={onClose} size="2xl">
        <ModalContent>
          <ModalHeader>Report Details</ModalHeader>
          <ModalBody>
            {selectedReport && (
              <div className="space-y-6">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <h4 className="font-semibold text-gray-700">Report Information</h4>
                    <div className="mt-2 space-y-2">
                      <div>
                        <span className="text-sm text-gray-500">Title:</span>
                        <div className="font-medium">{selectedReport.title}</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Type:</span>
                        <div className="font-medium">{selectedReport.type}</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Status:</span>
                        <div className="font-medium">{selectedReport.status}</div>
                      </div>
                    </div>
                  </div>
                  <div>
                    <h4 className="font-semibold text-gray-700">File Details</h4>
                    <div className="mt-2 space-y-2">
                      <div>
                        <span className="text-sm text-gray-500">Format:</span>
                        <div className="font-medium">{selectedReport.format}</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">File Size:</span>
                        <div className="font-medium">{selectedReport.fileSize}</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Generated By:</span>
                        <div className="font-medium">{selectedReport.generatedBy}</div>
                      </div>
                    </div>
                  </div>
                </div>

                <div>
                  <h4 className="font-semibold text-gray-700">Description</h4>
                  <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                    <p className="text-gray-700">{selectedReport.description}</p>
                  </div>
                </div>

                <div>
                  <h4 className="font-semibold text-gray-700">Generated On</h4>
                  <div className="mt-2">
                    <div className="flex items-center gap-2">
                      <FaCalendar className="text-gray-400" />
                      <span className="font-medium">{selectedReport.date} at {selectedReport.time}</span>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </ModalBody>
          <ModalFooter>
            <Button color="primary" variant="light" startContent={<FaDownload />}>
              Download
            </Button>
            <Button color="secondary" variant="light" startContent={<FaPrint />}>
              Print
            </Button>
            <Button color="primary" onPress={onClose}>
              Close
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </div>
  );
};

export default Reports; 