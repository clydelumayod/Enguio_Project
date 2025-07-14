"use client";
import React, { useState, useEffect } from "react";
import { Card, CardBody, CardHeader, Button, Input, Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Chip, Pagination, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, useDisclosure, Select, SelectItem, Textarea } from "@nextui-org/react";
import { FaPlus, FaSearch, FaEdit, FaTrash, FaEye, FaFilter, FaDownload, FaUpload, FaMinus, FaPlus as FaPlusIcon } from "react-icons/fa";

const StockAdjustment = () => {
  const [adjustments, setAdjustments] = useState([]);
  const [filteredAdjustments, setFilteredAdjustments] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedType, setSelectedType] = useState("all");
  const [selectedStatus, setSelectedStatus] = useState("all");
  const [page, setPage] = useState(1);
  const [rowsPerPage] = useState(10);
  const { isOpen, onOpen, onClose } = useDisclosure();
  const [editingAdjustment, setEditingAdjustment] = useState(null);
  const [isLoading, setIsLoading] = useState(false);

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
        return "success";
      case "Pending":
        return "warning";
      case "Rejected":
        return "danger";
      default:
        return "default";
    }
  };

  const getTypeColor = (type) => {
    switch (type) {
      case "Addition":
        return "success";
      case "Subtraction":
        return "danger";
      default:
        return "default";
    }
  };

  const handleEdit = (adjustment) => {
    setEditingAdjustment(adjustment);
    onOpen();
  };

  const handleSave = () => {
    if (editingAdjustment) {
      setAdjustments(prev => 
        prev.map(item => 
          item.id === editingAdjustment.id ? editingAdjustment : item
        )
      );
    }
    onClose();
    setEditingAdjustment(null);
  };

  const handleDelete = (id) => {
    setAdjustments(prev => prev.filter(item => item.id !== id));
  };

  const adjustmentTypes = ["all", "Addition", "Subtraction"];
  const statuses = ["all", "Approved", "Pending", "Rejected"];

  const pages = Math.ceil(filteredAdjustments.length / rowsPerPage);
  const items = filteredAdjustments.slice((page - 1) * rowsPerPage, page * rowsPerPage);

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Stock Adjustment</h1>
          <p className="text-gray-600">Manage inventory adjustments and stock modifications</p>
        </div>
        <div className="flex gap-3">
          <Button color="primary" startContent={<FaUpload />}>
            Import
          </Button>
          <Button color="success" startContent={<FaDownload />}>
            Export
          </Button>
          <Button color="primary" startContent={<FaPlus />}>
            New Adjustment
          </Button>
        </div>
      </div>

      {/* Filters and Search */}
      <Card>
        <CardBody>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="md:col-span-2">
              <Input
                placeholder="Search adjustments..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                startContent={<FaSearch className="text-gray-400" />}
                className="w-full"
              />
            </div>
            <div>
              <Select
                placeholder="Adjustment Type"
                selectedKeys={[selectedType]}
                onChange={(e) => setSelectedType(e.target.value)}
                startContent={<FaFilter className="text-gray-400" />}
              >
                {adjustmentTypes.map((type) => (
                  <SelectItem key={type} value={type}>
                    {type === "all" ? "All Types" : type}
                  </SelectItem>
                ))}
              </Select>
            </div>
            <div>
              <Select
                placeholder="Status"
                selectedKeys={[selectedStatus]}
                onChange={(e) => setSelectedStatus(e.target.value)}
                startContent={<FaFilter className="text-gray-400" />}
              >
                {statuses.map((status) => (
                  <SelectItem key={status} value={status}>
                    {status === "all" ? "All Status" : status}
                  </SelectItem>
                ))}
              </Select>
            </div>
          </div>
        </CardBody>
      </Card>

      {/* Adjustments Table */}
      <Card>
        <CardHeader>
          <div className="flex justify-between items-center">
            <h3 className="text-xl font-semibold">Adjustments</h3>
            <div className="text-sm text-gray-500">
              {filteredAdjustments.length} adjustments found
            </div>
          </div>
        </CardHeader>
        <CardBody>
          <Table aria-label="Stock adjustments table">
            <TableHeader>
              <TableColumn>PRODUCT</TableColumn>
              <TableColumn>TYPE</TableColumn>
              <TableColumn>QUANTITY</TableColumn>
              <TableColumn>REASON</TableColumn>
              <TableColumn>ADJUSTED BY</TableColumn>
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
                      color={getTypeColor(item.adjustmentType)} 
                      variant="flat"
                      startContent={item.adjustmentType === "Addition" ? <FaPlusIcon /> : <FaMinus />}
                    >
                      {item.adjustmentType}
                    </Chip>
                  </TableCell>
                  <TableCell>
                    <div className="font-semibold">{item.quantity}</div>
                  </TableCell>
                  <TableCell>
                    <div className="max-w-xs truncate" title={item.reason}>
                      {item.reason}
                    </div>
                  </TableCell>
                  <TableCell>{item.adjustedBy}</TableCell>
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
                      <Button isIconOnly size="sm" variant="light" onPress={() => handleEdit(item)}>
                        <FaEdit className="text-blue-500" />
                      </Button>
                      <Button isIconOnly size="sm" variant="light">
                        <FaEye className="text-green-500" />
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

      {/* Edit Modal */}
      <Modal isOpen={isOpen} onClose={onClose} size="2xl">
        <ModalContent>
          <ModalHeader>Edit Adjustment</ModalHeader>
          <ModalBody>
            {editingAdjustment && (
              <div className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <Input
                    label="Product Name"
                    value={editingAdjustment.productName}
                    onChange={(e) => setEditingAdjustment({...editingAdjustment, productName: e.target.value})}
                  />
                  <Input
                    label="Product ID"
                    value={editingAdjustment.productId}
                    onChange={(e) => setEditingAdjustment({...editingAdjustment, productId: e.target.value})}
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <Select
                    label="Adjustment Type"
                    selectedKeys={[editingAdjustment.adjustmentType]}
                    onChange={(e) => setEditingAdjustment({...editingAdjustment, adjustmentType: e.target.value})}
                  >
                    {adjustmentTypes.filter(type => type !== "all").map((type) => (
                      <SelectItem key={type} value={type}>
                        {type}
                      </SelectItem>
                    ))}
                  </Select>
                  <Input
                    label="Quantity"
                    type="number"
                    value={editingAdjustment.quantity}
                    onChange={(e) => setEditingAdjustment({...editingAdjustment, quantity: parseInt(e.target.value)})}
                  />
                </div>
                <Input
                  label="Reason"
                  value={editingAdjustment.reason}
                  onChange={(e) => setEditingAdjustment({...editingAdjustment, reason: e.target.value})}
                />
                <div className="grid grid-cols-2 gap-4">
                  <Input
                    label="Adjusted By"
                    value={editingAdjustment.adjustedBy}
                    onChange={(e) => setEditingAdjustment({...editingAdjustment, adjustedBy: e.target.value})}
                  />
                  <Select
                    label="Status"
                    selectedKeys={[editingAdjustment.status]}
                    onChange={(e) => setEditingAdjustment({...editingAdjustment, status: e.target.value})}
                  >
                    {statuses.filter(status => status !== "all").map((status) => (
                      <SelectItem key={status} value={status}>
                        {status}
                      </SelectItem>
                    ))}
                  </Select>
                </div>
                <Textarea
                  label="Notes"
                  value={editingAdjustment.notes}
                  onChange={(e) => setEditingAdjustment({...editingAdjustment, notes: e.target.value})}
                  placeholder="Additional notes..."
                />
              </div>
            )}
          </ModalBody>
          <ModalFooter>
            <Button color="danger" variant="light" onPress={onClose}>
              Cancel
            </Button>
            <Button color="primary" onPress={handleSave}>
              Save Changes
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </div>
  );
};

export default StockAdjustment; 