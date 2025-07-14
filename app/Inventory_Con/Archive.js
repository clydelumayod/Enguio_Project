"use client";
import React, { useState, useEffect } from "react";
import { Card, CardBody, CardHeader, Button, Input, Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Chip, Pagination, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, useDisclosure, Select, SelectItem, Textarea } from "@nextui-org/react";
import { FaSearch, FaEye, FaFilter, FaDownload, FaCalendar, FaArchive, FaTrash, FaUndo, FaHistory, FaBox } from "react-icons/fa";

const Archive = () => {
  const [archivedItems, setArchivedItems] = useState([]);
  const [filteredItems, setFilteredItems] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedType, setSelectedType] = useState("all");
  const [selectedDateRange, setSelectedDateRange] = useState("all");
  const [page, setPage] = useState(1);
  const [rowsPerPage] = useState(10);
  const { isOpen, onOpen, onClose } = useDisclosure();
  const [selectedItem, setSelectedItem] = useState(null);
  const [isLoading, setIsLoading] = useState(false);

  // Sample data - replace with actual API calls
  const sampleData = [
    {
      id: 1,
      name: "Paracetamol 500mg",
      type: "Product",
      category: "Pain Relief",
      archivedBy: "John Doe",
      archivedDate: "2024-01-15",
      archivedTime: "10:30 AM",
      reason: "Discontinued product",
      originalStock: 150,
      originalValue: 750.00,
      status: "Archived",
      notes: "Product discontinued by manufacturer"
    },
    {
      id: 2,
      name: "Amoxicillin 250mg",
      type: "Product",
      category: "Antibiotics",
      archivedBy: "Jane Smith",
      archivedDate: "2024-01-14",
      archivedTime: "02:15 PM",
      reason: "Expired stock",
      originalStock: 75,
      originalValue: 956.25,
      status: "Archived",
      notes: "All stock expired and disposed"
    },
    {
      id: 3,
      name: "Vitamin C 1000mg",
      type: "Product",
      category: "Vitamins",
      archivedBy: "Mike Johnson",
      archivedDate: "2024-01-13",
      archivedTime: "09:45 AM",
      reason: "Supplier change",
      originalStock: 200,
      originalValue: 1650.00,
      status: "Archived",
      notes: "Switching to new supplier"
    },
    {
      id: 4,
      name: "Omeprazole 20mg",
      type: "Product",
      category: "Gastrointestinal",
      archivedBy: "Sarah Wilson",
      archivedDate: "2024-01-12",
      archivedTime: "04:20 PM",
      reason: "Quality issues",
      originalStock: 45,
      originalValue: 715.50,
      status: "Archived",
      notes: "Quality control issues reported"
    },
    {
      id: 5,
      name: "Ibuprofen 400mg",
      type: "Product",
      category: "Pain Relief",
      archivedBy: "David Brown",
      archivedDate: "2024-01-11",
      archivedTime: "11:30 AM",
      reason: "Regulatory compliance",
      originalStock: 120,
      originalValue: 816.00,
      status: "Archived",
      notes: "New regulatory requirements not met"
    },
    {
      id: 6,
      name: "Aspirin 100mg",
      type: "Product",
      category: "Pain Relief",
      archivedBy: "Lisa Chen",
      archivedDate: "2024-01-10",
      archivedTime: "08:15 AM",
      reason: "Low demand",
      originalStock: 80,
      originalValue: 320.00,
      status: "Archived",
      notes: "Poor sales performance"
    }
  ];

  useEffect(() => {
    setArchivedItems(sampleData);
    setFilteredItems(sampleData);
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
        return "warning";
      case "Restored":
        return "success";
      case "Deleted":
        return "danger";
      default:
        return "default";
    }
  };

  const getTypeColor = (type) => {
    switch (type) {
      case "Product":
        return "primary";
      case "Category":
        return "secondary";
      case "Supplier":
        return "success";
      default:
        return "default";
    }
  };

  const handleViewDetails = (item) => {
    setSelectedItem(item);
    onOpen();
  };

  const handleRestore = (id) => {
    // Simulate restore operation
    setIsLoading(true);
    setTimeout(() => {
      setArchivedItems(prev => prev.filter(item => item.id !== id));
      setIsLoading(false);
      alert("Item restored successfully!");
    }, 1000);
  };

  const handleDelete = (id) => {
    if (confirm("Are you sure you want to permanently delete this item? This action cannot be undone.")) {
      setIsLoading(true);
      setTimeout(() => {
        setArchivedItems(prev => prev.filter(item => item.id !== id));
        setIsLoading(false);
        alert("Item permanently deleted!");
      }, 1000);
    }
  };

  const itemTypes = ["all", "Product", "Category", "Supplier"];
  const dateRanges = ["all", "today", "week", "month"];

  const pages = Math.ceil(filteredItems.length / rowsPerPage);
  const items = filteredItems.slice((page - 1) * rowsPerPage, page * rowsPerPage);

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Archive</h1>
          <p className="text-gray-600">Manage archived inventory items and records</p>
        </div>
        <div className="flex gap-3">
          <Button color="success" startContent={<FaDownload />}>
            Export Archive
          </Button>
        </div>
      </div>

      {/* Archive Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <Card>
          <CardBody>
            <div className="flex items-center gap-3">
              <div className="p-3 bg-yellow-100 rounded-lg">
                <FaArchive className="text-yellow-600 text-xl" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Total Archived</p>
                <p className="text-2xl font-bold">{archivedItems.length}</p>
              </div>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <div className="flex items-center gap-3">
              <div className="p-3 bg-red-100 rounded-lg">
                <FaTrash className="text-red-600 text-xl" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Products</p>
                <p className="text-2xl font-bold">{archivedItems.filter(item => item.type === "Product").length}</p>
              </div>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <div className="flex items-center gap-3">
              <div className="p-3 bg-blue-100 rounded-lg">
                <FaHistory className="text-blue-600 text-xl" />
              </div>
              <div>
                <p className="text-sm text-gray-500">This Month</p>
                <p className="text-2xl font-bold">
                  {archivedItems.filter(item => {
                    const itemDate = new Date(item.archivedDate);
                    const monthAgo = new Date();
                    monthAgo.setMonth(monthAgo.getMonth() - 1);
                    return itemDate >= monthAgo;
                  }).length}
                </p>
              </div>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <div className="flex items-center gap-3">
              <div className="p-3 bg-green-100 rounded-lg">
                <FaUndo className="text-green-600 text-xl" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Restored</p>
                <p className="text-2xl font-bold">0</p>
              </div>
            </div>
          </CardBody>
        </Card>
      </div>

      {/* Filters and Search */}
      <Card>
        <CardBody>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="md:col-span-2">
              <Input
                placeholder="Search archived items..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                startContent={<FaSearch className="text-gray-400" />}
                className="w-full"
              />
            </div>
            <div>
              <Select
                placeholder="Item Type"
                selectedKeys={[selectedType]}
                onChange={(e) => setSelectedType(e.target.value)}
                startContent={<FaFilter className="text-gray-400" />}
              >
                {itemTypes.map((type) => (
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

      {/* Archived Items Table */}
      <Card>
        <CardHeader>
          <div className="flex justify-between items-center">
            <h3 className="text-xl font-semibold">Archived Items</h3>
            <div className="text-sm text-gray-500">
              {filteredItems.length} items found
            </div>
          </div>
        </CardHeader>
        <CardBody>
          <Table aria-label="Archived items table">
            <TableHeader>
              <TableColumn>ITEM NAME</TableColumn>
              <TableColumn>TYPE</TableColumn>
              <TableColumn>CATEGORY</TableColumn>
              <TableColumn>ARCHIVED BY</TableColumn>
              <TableColumn>DATE & TIME</TableColumn>
              <TableColumn>REASON</TableColumn>
              <TableColumn>ORIGINAL VALUE</TableColumn>
              <TableColumn>ACTIONS</TableColumn>
            </TableHeader>
            <TableBody>
              {items.map((item) => (
                <TableRow key={item.id}>
                  <TableCell>
                    <div>
                      <div className="font-semibold">{item.name}</div>
                      <div className="text-sm text-gray-500">ID: {item.id}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Chip 
                      color={getTypeColor(item.type)} 
                      variant="flat"
                      startContent={<FaBox />}
                    >
                      {item.type}
                    </Chip>
                  </TableCell>
                  <TableCell>{item.category}</TableCell>
                  <TableCell>{item.archivedBy}</TableCell>
                  <TableCell>
                    <div>
                      <div className="font-semibold">{item.archivedDate}</div>
                      <div className="text-sm text-gray-500">{item.archivedTime}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="max-w-xs truncate" title={item.reason}>
                      {item.reason}
                    </div>
                  </TableCell>
                  <TableCell>
                    <div>
                      <div className="font-semibold">₱{item.originalValue.toFixed(2)}</div>
                      <div className="text-sm text-gray-500">{item.originalStock} units</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-2">
                      <Button isIconOnly size="sm" variant="light" onPress={() => handleViewDetails(item)}>
                        <FaEye className="text-blue-500" />
                      </Button>
                      <Button isIconOnly size="sm" variant="light" onPress={() => handleRestore(item.id)}>
                        <FaUndo className="text-green-500" />
                      </Button>
                      <Button isIconOnly size="sm" variant="light" onPress={() => handleDelete(item.id)}>
                        <FaTrash className="text-red-500" />
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

      {/* Item Details Modal */}
      <Modal isOpen={isOpen} onClose={onClose} size="2xl">
        <ModalContent>
          <ModalHeader>Archived Item Details</ModalHeader>
          <ModalBody>
            {selectedItem && (
              <div className="space-y-6">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <h4 className="font-semibold text-gray-700">Item Information</h4>
                    <div className="mt-2 space-y-2">
                      <div>
                        <span className="text-sm text-gray-500">Name:</span>
                        <div className="font-medium">{selectedItem.name}</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Type:</span>
                        <div className="font-medium">{selectedItem.type}</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Category:</span>
                        <div className="font-medium">{selectedItem.category}</div>
                      </div>
                    </div>
                  </div>
                  <div>
                    <h4 className="font-semibold text-gray-700">Archive Details</h4>
                    <div className="mt-2 space-y-2">
                      <div>
                        <span className="text-sm text-gray-500">Status:</span>
                        <div className="font-medium">{selectedItem.status}</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Archived By:</span>
                        <div className="font-medium">{selectedItem.archivedBy}</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Date & Time:</span>
                        <div className="font-medium">{selectedItem.archivedDate} at {selectedItem.archivedTime}</div>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <h4 className="font-semibold text-gray-700">Original Stock</h4>
                    <div className="mt-2 space-y-2">
                      <div>
                        <span className="text-sm text-gray-500">Quantity:</span>
                        <div className="font-medium">{selectedItem.originalStock} units</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Value:</span>
                        <div className="font-medium">₱{selectedItem.originalValue.toFixed(2)}</div>
                      </div>
                    </div>
                  </div>
                  <div>
                    <h4 className="font-semibold text-gray-700">Archive Reason</h4>
                    <div className="mt-2">
                      <div className="p-3 bg-gray-50 rounded-lg">
                        <p className="text-gray-700">{selectedItem.reason}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <div>
                  <h4 className="font-semibold text-gray-700">Notes</h4>
                  <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                    <p className="text-gray-700">{selectedItem.notes}</p>
                  </div>
                </div>
              </div>
            )}
          </ModalBody>
          <ModalFooter>
            <Button color="success" variant="light" startContent={<FaUndo />}>
              Restore Item
            </Button>
            <Button color="danger" variant="light" startContent={<FaTrash />}>
              Delete Permanently
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

export default Archive; 