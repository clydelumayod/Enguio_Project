"use client";
import React, { useState, useEffect } from "react";
import { Card, CardBody, CardHeader, Button, Input, Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Chip, Pagination, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, useDisclosure, Select, SelectItem, Textarea } from "@nextui-org/react";
import { FaSearch, FaEye, FaFilter, FaDownload, FaCalendar, FaMapMarkerAlt, FaTruck, FaBox, FaUser } from "react-icons/fa";

const MovementHistory = () => {
  const [movements, setMovements] = useState([]);
  const [filteredMovements, setFilteredMovements] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedType, setSelectedType] = useState("all");
  const [selectedLocation, setSelectedLocation] = useState("all");
  const [selectedDateRange, setSelectedDateRange] = useState("all");
  const [page, setPage] = useState(1);
  const [rowsPerPage] = useState(10);
  const { isOpen, onOpen, onClose } = useDisclosure();
  const [selectedMovement, setSelectedMovement] = useState(null);
  const [isLoading, setIsLoading] = useState(false);

  // Sample data - replace with actual API calls
  const sampleData = [
    {
      id: 1,
      productName: "Paracetamol 500mg",
      productId: "MED001",
      movementType: "Transfer",
      quantity: 50,
      fromLocation: "Warehouse A",
      toLocation: "Pharmacy Store",
      movedBy: "John Doe",
      date: "2024-01-15",
      time: "10:30 AM",
      status: "Completed",
      notes: "Regular stock transfer to pharmacy",
      reference: "TR-2024-001"
    },
    {
      id: 2,
      productName: "Amoxicillin 250mg",
      productId: "MED002",
      movementType: "Receipt",
      quantity: 100,
      fromLocation: "Supplier",
      toLocation: "Warehouse B",
      movedBy: "Jane Smith",
      date: "2024-01-14",
      time: "02:15 PM",
      status: "Completed",
      notes: "New shipment received",
      reference: "RC-2024-002"
    },
    {
      id: 3,
      productName: "Vitamin C 1000mg",
      productId: "MED003",
      movementType: "Return",
      quantity: 25,
      fromLocation: "Pharmacy Store",
      toLocation: "Warehouse A",
      movedBy: "Mike Johnson",
      date: "2024-01-13",
      time: "09:45 AM",
      status: "Completed",
      notes: "Customer return - unused medication",
      reference: "RT-2024-003"
    },
    {
      id: 4,
      productName: "Omeprazole 20mg",
      productId: "MED004",
      movementType: "Adjustment",
      quantity: -10,
      fromLocation: "Pharmacy Store",
      toLocation: "Disposal",
      movedBy: "Sarah Wilson",
      date: "2024-01-12",
      time: "04:20 PM",
      status: "Completed",
      notes: "Expired products disposal",
      reference: "ADJ-2024-004"
    },
    {
      id: 5,
      productName: "Ibuprofen 400mg",
      productId: "MED005",
      movementType: "Transfer",
      quantity: 75,
      fromLocation: "Warehouse B",
      toLocation: "Convenience Store",
      movedBy: "David Brown",
      date: "2024-01-11",
      time: "11:30 AM",
      status: "In Progress",
      notes: "Stock replenishment for convenience store",
      reference: "TR-2024-005"
    },
    {
      id: 6,
      productName: "Aspirin 100mg",
      productId: "MED006",
      movementType: "Receipt",
      quantity: 200,
      fromLocation: "Supplier",
      toLocation: "Warehouse A",
      movedBy: "Lisa Chen",
      date: "2024-01-10",
      time: "08:15 AM",
      status: "Completed",
      notes: "Bulk order received",
      reference: "RC-2024-006"
    }
  ];

  useEffect(() => {
    setMovements(sampleData);
    setFilteredMovements(sampleData);
  }, []);

  useEffect(() => {
    filterMovements();
  }, [searchTerm, selectedType, selectedLocation, selectedDateRange, movements]);

  const filterMovements = () => {
    let filtered = movements;

    if (searchTerm) {
      filtered = filtered.filter(item =>
        item.productName.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.productId.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.movedBy.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.reference.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    if (selectedType !== "all") {
      filtered = filtered.filter(item => item.movementType === selectedType);
    }

    if (selectedLocation !== "all") {
      filtered = filtered.filter(item => 
        item.fromLocation === selectedLocation || item.toLocation === selectedLocation
      );
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

    setFilteredMovements(filtered);
  };

  const getStatusColor = (status) => {
    switch (status) {
      case "Completed":
        return "success";
      case "In Progress":
        return "warning";
      case "Pending":
        return "primary";
      case "Cancelled":
        return "danger";
      default:
        return "default";
    }
  };

  const getTypeColor = (type) => {
    switch (type) {
      case "Transfer":
        return "primary";
      case "Receipt":
        return "success";
      case "Return":
        return "warning";
      case "Adjustment":
        return "secondary";
      default:
        return "default";
    }
  };

  const handleViewDetails = (movement) => {
    setSelectedMovement(movement);
    onOpen();
  };

  const movementTypes = ["all", "Transfer", "Receipt", "Return", "Adjustment"];
  const locations = ["all", "Warehouse A", "Warehouse B", "Pharmacy Store", "Convenience Store", "Supplier", "Disposal"];
  const dateRanges = ["all", "today", "week", "month"];

  const pages = Math.ceil(filteredMovements.length / rowsPerPage);
  const items = filteredMovements.slice((page - 1) * rowsPerPage, page * rowsPerPage);

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Movement History</h1>
          <p className="text-gray-600">Track all inventory movements and transfers</p>
        </div>
        <div className="flex gap-3">
          <Button color="success" startContent={<FaDownload />}>
            Export Report
          </Button>
        </div>
      </div>

      {/* Filters and Search */}
      <Card className="border-none shadow-lg rounded-xl bg-white">
        <CardBody>
          <div className="">
            <div className="md:col-span-2">
              <Input
                placeholder="Search movements..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                startContent={<FaSearch className="text-gray-400" />}
                className="w-full"
              />
            </div>
            <div>
              <Select
                placeholder="Movement Type"
                selectedKeys={[selectedType]}
                onChange={(e) => setSelectedType(e.target.value)}
                startContent={<FaFilter className="text-gray-400" />}
              >
                {movementTypes.map((type) => (
                  <SelectItem key={type} value={type}>
                    {type === "all" ? "All Types" : type}
                  </SelectItem>
                ))}
              </Select>
            </div>
            <div>
              <Select
                placeholder="Location"
                selectedKeys={[selectedLocation]}
                onChange={(e) => setSelectedLocation(e.target.value)}
                startContent={<FaMapMarkerAlt className="text-gray-400" />}
              >
                {locations.map((location) => (
                  <SelectItem key={location} value={location}>
                    {location === "all" ? "All Locations" : location}
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

      {/* Movements Table */}
      <Card className="border-none shadow-lg rounded-xl bg-white">
        <CardHeader>
          <div className="flex justify-between items-center">
            <h3 className="text-xl font-semibold">Movement Records</h3>
            <div className="text-sm text-gray-500">
              {filteredMovements.length} movements found
            </div>
          </div>
        </CardHeader>
        <CardBody>
          <Table aria-label="Movement history table" className="border-none rounded-lg">
            <TableHeader>
              <TableColumn>PRODUCT</TableColumn>
              <TableColumn>TYPE</TableColumn>
              <TableColumn>QUANTITY</TableColumn>
              <TableColumn>FROM</TableColumn>
              <TableColumn>TO</TableColumn>
              <TableColumn>MOVED BY</TableColumn>
              <TableColumn>DATE & TIME</TableColumn>
              <TableColumn>STATUS</TableColumn>
              <TableColumn>ACTIONS</TableColumn>
            </TableHeader>
            <TableBody>
              {items.map((item) => (
                <TableRow key={item.id}>
                  <TableCell>
                    <div>
                      <div className="font-semibold">{item.productName}</div>
                      <div className="text-sm text-gray-500">ID: {item.productId}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Chip 
                      color={getTypeColor(item.movementType)} 
                      variant="flat"
                      startContent={<FaTruck />}
                    >
                      {item.movementType}
                    </Chip>
                  </TableCell>
                  <TableCell>
                    <div className={`font-semibold ${item.quantity < 0 ? 'text-red-500' : 'text-green-500'}`}>
                      {item.quantity > 0 ? '+' : ''}{item.quantity}
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <FaMapMarkerAlt className="text-gray-400" />
                      <span>{item.fromLocation}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <FaMapMarkerAlt className="text-gray-400" />
                      <span>{item.toLocation}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <FaUser className="text-gray-400" />
                      <span>{item.movedBy}</span>
                    </div>
                  </TableCell>
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
                    <div className="flex gap-2">
                      <Button isIconOnly size="sm" variant="light" onPress={() => handleViewDetails(item)}>
                        <FaEye className="text-blue-500" />
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

      {/* Details Modal */}
      <Modal isOpen={isOpen} onClose={onClose} size="2xl">
        <ModalContent>
          <ModalHeader>Movement Details</ModalHeader>
          <ModalBody>
            {selectedMovement && (
              <div className="space-y-6">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <h4 className="font-semibold text-gray-700">Product Information</h4>
                    <div className="mt-2 space-y-2">
                      <div>
                        <span className="text-sm text-gray-500">Product Name:</span>
                        <div className="font-medium">{selectedMovement.productName}</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Product ID:</span>
                        <div className="font-medium">{selectedMovement.productId}</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Reference:</span>
                        <div className="font-medium">{selectedMovement.reference}</div>
                      </div>
                    </div>
                  </div>
                  <div>
                    <h4 className="font-semibold text-gray-700">Movement Details</h4>
                    <div className="mt-2 space-y-2">
                      <div>
                        <span className="text-sm text-gray-500">Type:</span>
                        <div className="font-medium">{selectedMovement.movementType}</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Quantity:</span>
                        <div className={`font-medium ${selectedMovement.quantity < 0 ? 'text-red-500' : 'text-green-500'}`}>
                          {selectedMovement.quantity > 0 ? '+' : ''}{selectedMovement.quantity}
                        </div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Status:</span>
                        <div className="font-medium">{selectedMovement.status}</div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <h4 className="font-semibold text-gray-700">From Location</h4>
                    <div className="mt-2">
                      <div className="flex items-center gap-2">
                        <FaMapMarkerAlt className="text-gray-400" />
                        <span className="font-medium">{selectedMovement.fromLocation}</span>
                      </div>
                    </div>
                  </div>
                  <div>
                    <h4 className="font-semibold text-gray-700">To Location</h4>
                    <div className="mt-2">
                      <div className="flex items-center gap-2">
                        <FaMapMarkerAlt className="text-gray-400" />
                        <span className="font-medium">{selectedMovement.toLocation}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <h4 className="font-semibold text-gray-700">Moved By</h4>
                    <div className="mt-2">
                      <div className="flex items-center gap-2">
                        <FaUser className="text-gray-400" />
                        <span className="font-medium">{selectedMovement.movedBy}</span>
                      </div>
                    </div>
                  </div>
                  <div>
                    <h4 className="font-semibold text-gray-700">Date & Time</h4>
                    <div className="mt-2">
                      <div className="flex items-center gap-2">
                        <FaCalendar className="text-gray-400" />
                        <span className="font-medium">{selectedMovement.date} at {selectedMovement.time}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div>
                  <h4 className="font-semibold text-gray-700">Notes</h4>
                  <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                    <p className="text-gray-700">{selectedMovement.notes}</p>
                  </div>
                </div>
              </div>
            )}
          </ModalBody>
          <ModalFooter>
            <Button color="primary" onPress={onClose}>
              Close
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </div>
  );
};

export default MovementHistory; 