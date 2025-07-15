"use client";
import React, { useState, useEffect } from "react";
import { Card, CardBody, CardHeader, Button, Input, Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Chip, Pagination, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, useDisclosure, Select, SelectItem, Textarea, Spinner } from "@nextui-org/react";
import { FaSearch, FaEye, FaFilter, FaDownload, FaCalendar, FaMapMarkerAlt, FaTruck, FaBox, FaUser, FaRedo } from "react-icons/fa";
import { toast, ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";

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
  const [locations, setLocations] = useState([]);

  // API call function
  const handleApiCall = async (action, data = {}) => {
    try {
      const response = await fetch('http://localhost/Enguio_Project/backend.php', {
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

      const result = await response.json();
      
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
        return "success";
      case "In Progress":
      case "Pending":
        return "warning";
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

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Movement History</h1>
          <p className="text-gray-600">Track all inventory movements and transfers</p>
        </div>
        <div className="flex gap-3">
          <Button 
            color="primary" 
            variant="light" 
            startContent={<FaRedo />}
            onPress={handleRefresh}
            isLoading={isLoading}
          >
            Refresh
          </Button>
          <Button color="success" startContent={<FaDownload />} onPress={handleExport}>
            Export Report
          </Button>
        </div>
      </div>

      {/* Filters and Search */}
      <Card className="border-none shadow-lg rounded-xl bg-white">
        <CardBody>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                <SelectItem key="all" value="all">All Locations</SelectItem>
                {locations.map((location) => (
                  <SelectItem key={location.location_name} value={location.location_name}>
                    {location.location_name}
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


      <Card className="border-none shadow-lg rounded-xl bg-white">
        <CardHeader>
          <div className="flex justify-between items-center">
            <h3 className="text-xl font-semibold">Movement Records</h3>
            <div className="text-sm text-gray-500">
              {isLoading ? (
                <div className="flex items-center gap-2">
                  <Spinner size="sm" />
                  Loading...
                </div>
              ) : (
                `${filteredMovements.length} movements found`
              )}
            </div>
          </div>
        </CardHeader>
        <CardBody>
          {isLoading ? (
            <div className="flex justify-center items-center py-12">
              <Spinner size="lg" />
            </div>
          ) : filteredMovements.length === 0 ? (
            <div className="text-center py-12">
              <FaBox className="mx-auto text-gray-400 text-4xl mb-4" />
              <p className="text-gray-500">No movement records found</p>
              <p className="text-sm text-gray-400">Try adjusting your filters or refresh the data</p>
            </div>
          ) : (
            <>
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
                    <TableRow key={`${item.id}-${item.productId}`}>
                      <TableCell>
                        <div>
                          <div className="font-semibold">{item.product_name}</div>
                          <div className="text-sm text-gray-500">ID: {item.productId}</div>
                          <div className="text-xs text-gray-400">{item.category}</div>
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
                          <div className="font-semibold">{formatDate(item.date)}</div>
                          <div className="text-sm text-gray-500">{formatTime(item.time)}</div>
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
              {pages > 1 && (
                <div className="flex justify-center mt-4">
                  <Pagination
                    total={pages}
                    page={page}
                    onChange={setPage}
                    showControls
                    color="primary"
                  />
                </div>
              )}
            </>
          )}
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
                        <div className="font-medium">{selectedMovement.product_name}</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Product ID:</span>
                        <div className="font-medium">{selectedMovement.productId}</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Category:</span>
                        <div className="font-medium">{selectedMovement.category}</div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500">Brand:</span>
                        <div className="font-medium">{selectedMovement.brand || 'N/A'}</div>
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
                      <div>
                        <span className="text-sm text-gray-500">Unit Price:</span>
                        <div className="font-medium">₱{selectedMovement.unit_price?.toFixed(2) || 'N/A'}</div>
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
                        <span className="font-medium">{formatDate(selectedMovement.date)} at {formatTime(selectedMovement.time)}</span>
                      </div>
                    </div>
                  </div>
                </div>

                {selectedMovement.description && (
                  <div>
                    <h4 className="font-semibold text-gray-700">Description</h4>
                    <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                      <p className="text-gray-700">{selectedMovement.description}</p>
                    </div>
                  </div>
                )}

                {selectedMovement.notes && selectedMovement.notes !== null && (
                  <div>
                    <h4 className="font-semibold text-gray-700">Notes</h4>
                    <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                      <p className="text-gray-700">{selectedMovement.notes}</p>
                    </div>
                  </div>
                )}
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