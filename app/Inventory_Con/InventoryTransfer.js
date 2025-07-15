"use client";

import React, { useState, useEffect } from "react";
import axios from "axios";
import { toast, ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import {
  ChevronUp,
  ChevronDown,
  Plus,
  X,
  Search,
  Truck,
  Trash2,
  Package,
} from "lucide-react";


function InventoryTransfer() {
  const [transfers, setTransfers] = useState([])
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [currentStep, setCurrentStep] = useState(1)
  const [loading, setLoading] = useState(false)
  const [searchTerm, setSearchTerm] = useState("")
  const [selectedProducts, setSelectedProducts] = useState([])
  const [availableProducts, setAvailableProducts] = useState([])
  const [checkedProducts, setCheckedProducts] = useState([])
  const [showProductSelection, setShowProductSelection] = useState(false)
  const [productSearchTerm, setProductSearchTerm] = useState("")
  const [selectedCategory, setSelectedCategory] = useState("All Product Category")
  const [selectedSupplier, setSelectedSupplier] = useState("All Suppliers")
  const [locations, setLocations] = useState([])
  const [staff, setStaff] = useState([])
  const [expandedTransfer, setExpandedTransfer] = useState(null)
  const [selectedTransfers, setSelectedTransfers] = useState([])
  const [showDeleteModal, setShowDeleteModal] = useState(false)
  const [transferToDelete, setTransferToDelete] = useState(null)

  // Step 1: Store Selection
  const [storeData, setStoreData] = useState({
    originalStore: "",
    destinationStore: "",
    storesConfirmed: false,
  })

  // Step 2: Transfer Information
  const [transferInfo, setTransferInfo] = useState({
    transferredBy: "",
    receivedBy: "",
    deliveryDate: "",
  })

  const API_BASE_URL = "http://localhost/enguio/Api/backend.php"

  // API function
  async function handleApiCall(action, data = {}) {
    const payload = { action, ...data }
    console.log("🚀 API Call Payload:", payload)

    try {
      const response = await fetch(API_BASE_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      })

      const resData = await response.json()
      console.log("✅ API Success Response:", resData)

      if (resData && typeof resData === "object") {
        if (!resData.success) {
          console.warn("⚠️ API responded with failure:", resData.message || resData)
        }
        return resData
      } else {
        console.warn("⚠️ Unexpected API response format:", resData)
        return {
          success: false,
          message: "Unexpected response format",
          data: resData,
        }
      }
    } catch (error) {
      console.error("❌ API Call Error:", error)
      return {
        success: false,
        message: error.message,
        error: "REQUEST_ERROR",
      }
    }
  }

  // Load data functions
  const loadTransfers = async () => {
    setLoading(true)
    try {
      const response = await handleApiCall("get_transfers_with_details")
      console.log("🔥 Transfers Loaded from API:", response)

      if (response.success && Array.isArray(response.data)) {
        console.log("✅ Number of transfers received:", response.data.length)
        console.log("📋 Transfer details:", response.data)
        setTransfers(response.data)
      } else {
        console.warn("⚠️ No transfers found or invalid format")
        console.log("🔍 Response structure:", response)
        setTransfers([])
      }
    } catch (error) {
      console.error("❌ Error loading transfers:", error)
      toast.error("Failed to load transfers")
      setTransfers([])
    } finally {
      setLoading(false)
    }
  }

  const loadAvailableProducts = async () => {
    try {
      console.log("Loading warehouse products...")
      const response = await handleApiCall("get_products")
      if (response.success && Array.isArray(response.data)) {
        console.log("✅ Loaded warehouse products:", response.data.length)
        setAvailableProducts(response.data)
      } else {
        console.warn("⚠️ No products found from API")
        setAvailableProducts([])
      }
    } catch (error) {
      console.error("Error loading products:", error)
      toast.error("Failed to load warehouse products")
      setAvailableProducts([])
    }
  }

  const loadLocations = async () => {
    try {
      const res = await handleApiCall("get_locations")
      console.log("📦 API Response from get_locations:", res)
      if (res.success && Array.isArray(res.data)) {
        setLocations(res.data)
      } else {
        console.warn("⚠️ No locations found or invalid response")
        setLocations([])
      }
    } catch (error) {
      console.error("❌ Failed to load locations:", error)
      setLocations([])
    }
  }

  // Load staff
  const loadStaff = async () => {
    try {
      const response = await handleApiCall("get_inventory_staff")
      if (response.success) {
        setStaff(response.data)
        } else {
          console.error("Failed to load inventory staff")
        }
      } catch (err) {
        console.error("Error loading staff:", err)
      }
    }

  useEffect(() => {
    loadTransfers()
    loadAvailableProducts()
    loadLocations()
    loadStaff()
  }, [])

  // Fixed transfer submission function
  const handleTransferSubmit = async () => {
    const productsToTransfer = selectedProducts.filter((p) => p.transfer_quantity > 0)

    if (productsToTransfer.length === 0) {
      toast.error("Please add at least one product with quantity > 0")
      return
    }

    if (!storeData.originalStore || !storeData.destinationStore) {
      toast.error("Please select both original and destination stores")
      return
    }

    if (!transferInfo.transferredBy) {
      toast.error("Please select who is transferring the products")
      return
    }

    setLoading(true)
    try {
      // Find location IDs
      const sourceLocation = locations.find((loc) => loc.location_name === storeData.originalStore)
      const destinationLocation = locations.find((loc) => loc.location_name === storeData.destinationStore)

      if (!sourceLocation || !destinationLocation) {
        toast.error("Invalid location selection")
        setLoading(false)
        return
      }

      // Find employee ID
      const transferEmployee = staff.find((emp) => emp.name === transferInfo.transferredBy)
      if (!transferEmployee) {
        toast.error("Invalid employee selection")
        setLoading(false)
        return
      }

      // Prepare transfer data
      const transferData = {
        source_location_id: sourceLocation.location_id,
        destination_location_id: destinationLocation.location_id,
        employee_id: transferEmployee.emp_id,
        status: "approved", // Use 'approved' to match database enum
        delivery_date: transferInfo.deliveryDate || null,
        products: productsToTransfer.map((product) => ({
          product_id: product.product_id,
          quantity: product.transfer_quantity,
        })),
      }

      console.log("📦 Sending transfer data:", transferData)
      const response = await handleApiCall("create_transfer", transferData)
      console.log("📥 Transfer creation response:", response)

      if (response.success) {
        toast.success("Transfer approved successfully! Products have been added to destination store.")
        console.log("✅ Transfer created with ID:", response.transfer_id)

        // Reset form
        setShowCreateModal(false)
        setCurrentStep(1)
        setStoreData({ originalStore: "", destinationStore: "", storesConfirmed: false })
        setTransferInfo({ transferredBy: "", receivedBy: "", deliveryDate: "" })
        setSelectedProducts([])
        setCheckedProducts([])

        // Reload transfers to show the new one
        console.log("🔄 Reloading transfers...")
        await loadTransfers()
        await loadAvailableProducts() // Reload products to update quantities
      } else {
        console.error("❌ Transfer creation failed:", response.message)
        toast.error(response.message || "Failed to create transfer")
      }
    } catch (error) {
      console.error("Error creating transfer:", error)
      toast.error("Failed to create transfer: " + error.message)
    } finally {
      setLoading(false)
    }
  }

  const handleCreateTransfer = () => {
    setCurrentStep(1)
    setStoreData({ originalStore: "", destinationStore: "", storesConfirmed: false })
    setTransferInfo({ transferredBy: "", receivedBy: "", deliveryDate: "" })
    setSelectedProducts([])
    setCheckedProducts([])
    setShowCreateModal(true)
  }

  const handleConfirmStores = () => {
    if (!storeData.originalStore || !storeData.destinationStore) {
      toast.error("Please select both original and destination stores")
      return
    }
    if (storeData.originalStore === storeData.destinationStore) {
      toast.error("Original and destination stores must be different")
      return
    }
    setStoreData((prev) => ({ ...prev, storesConfirmed: true }))
    setCurrentStep(2)
  }

  const handleNextToProducts = () => {
    if (!transferInfo.transferredBy) {
      toast.error("Transferred by (Original Store) is required")
      return
    }
    setCurrentStep(3)
  }

  const handleProductCheck = (productId, checked) => {
    if (checked) {
      setCheckedProducts((prev) => [...prev, productId])
    } else {
      setCheckedProducts((prev) => prev.filter((id) => id !== productId))
    }
  }

  const handleSelectProducts = () => {
    const selected = availableProducts
      .filter((p) => checkedProducts.includes(p.product_id))
      .map((p) => ({
        ...p,
        transfer_quantity: 0,
      }))
    setSelectedProducts(selected)
    setShowProductSelection(false)
  }

  const updateTransferQuantity = (productId, quantity) => {
    const newQuantity = Number.parseInt(quantity) || 0;
    
    // Find the product to check available quantity
    const product = selectedProducts.find(p => p.product_id === productId);
    const availableQty = product?.quantity || 0;
    const finalQuantity = Math.min(newQuantity, availableQty);
    
    // Show warning if quantity exceeds available (outside of state update)
    if (newQuantity > availableQty) {
      toast.warning(`Quantity reduced to available amount: ${availableQty}`);
    }
    
    setSelectedProducts((prev) =>
      prev.map((product) => {
        if (product.product_id === productId) {
          return { ...product, transfer_quantity: finalQuantity };
        }
        return product;
      }),
    )
  }

  const removeProduct = (productId) => {
    setSelectedProducts((prev) => prev.filter((product) => product.product_id !== productId))
    setCheckedProducts((prev) => prev.filter((id) => id !== productId))
  }

  const handleStatusUpdate = async (transferId, currentStatus) => {
    // Since transfers are now completed immediately, we'll just show a message
    toast.info("Transfer status: " + currentStatus + " - Products have been immediately added to destination store")
  }

  const handleDeleteTransfer = async (transferId) => {
    try {
      const response = await handleApiCall("delete_transfer", {
        transfer_header_id: transferId,
      })

      if (response.success) {
        toast.success("Transfer deleted successfully")
        loadTransfers()
        setShowDeleteModal(false)
        setTransferToDelete(null)
      } else {
        toast.error(response.message || "Failed to delete transfer")
      }
    } catch (error) {
      console.error("Error deleting transfer:", error)
      toast.error("Failed to delete transfer")
    }
  }

  const filteredTransfers = transfers.filter(
    (transfer) =>
      transfer.transfer_header_id?.toString().toLowerCase().includes(searchTerm.toLowerCase()) ||
      transfer.source_location_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      transfer.destination_location_name?.toLowerCase().includes(searchTerm.toLowerCase()),
  )

  const filteredProducts = availableProducts.filter((product) => {
    const matchesSearch =
      product.product_name.toLowerCase().includes(productSearchTerm.toLowerCase()) ||
      product.barcode.includes(productSearchTerm) ||
      (product.sku && product.sku.toLowerCase().includes(productSearchTerm.toLowerCase()))

    const matchesCategory = selectedCategory === "All Product Category" || product.category === selectedCategory
    const matchesSupplier = selectedSupplier === "All Suppliers" || product.supplier_name === selectedSupplier

    return matchesSearch && matchesCategory && matchesSupplier
  })

  // Get unique categories and suppliers from warehouse products
  const categories = [...new Set(availableProducts.map((p) => p.category).filter(Boolean))]
  const suppliers = [...new Set(availableProducts.map((p) => p.supplier_name).filter(Boolean))]

  // Main Transfer List View
  if (!showCreateModal) {
    return (
      <div className="p-6 bg-gray-50 min-h-screen">
        {/* Header */}
        <div className="mb-6">
          <div className="flex items-center text-sm text-gray-600 mb-2">
            <span>Inventory Management</span>
            <div className="mx-2">{">"}</div>
            <span className="text-blue-600">Inventory Transfer</span>
          </div>
          <h1 className="text-2xl font-bold text-gray-900">Inventory Transfer</h1>
        </div>

        {/* Action Bar */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
          <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div className="flex flex-wrap items-center gap-3">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <input
                  type="text"
                  placeholder="Search transfers..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-10 pr-4 py-2 w-64 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>
            <div className="flex items-center gap-2">
              <button
                onClick={handleCreateTransfer}
                className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center gap-2 whitespace-nowrap"
              >
                <Plus className="h-4 w-4" />
                <span>Create Transfer</span>
              </button>
            </div>
          </div>
        </div>

        {/* Transfer Statistics */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-2xl font-bold text-blue-600">{transfers.length}</div>
              <div className="text-sm text-gray-600">Total Transfers</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-green-600">
                {transfers.reduce((sum, transfer) => sum + (transfer.total_products || 0), 0)}
              </div>
              <div className="text-sm text-gray-600">Total Products Transferred</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-purple-600">
                ₱
                {transfers
                  .reduce((sum, transfer) => sum + (Number.parseFloat(transfer.total_value) || 0), 0)
                  .toFixed(2)}
              </div>
              <div className="text-sm text-gray-600">Total Transfer Value</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-orange-600">
                {transfers.filter((t) => t.status === "New").length}
              </div>
              <div className="text-sm text-gray-600">Pending Transfers</div>
            </div>
          </div>
          
          {/* Additional Transfer Summary */}
          <div className="mt-6 pt-6 border-t border-gray-200">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="text-center">
                <div className="text-lg font-semibold text-gray-700">
                  {transfers.filter((t) => t.status === "Completed").length}
                </div>
                <div className="text-sm text-gray-600">Completed Transfers</div>
              </div>
              <div className="text-center">
                <div className="text-lg font-semibold text-gray-700">
                  {transfers.filter((t) => t.status === "In Storage").length}
                </div>
                <div className="text-sm text-gray-600">In Storage</div>
              </div>
              <div className="text-center">
                <div className="text-lg font-semibold text-gray-700">
                  {transfers.filter((t) => t.status === "Transferring").length}
                </div>
                <div className="text-sm text-gray-600">In Transit</div>
              </div>
            </div>
          </div>
        </div>

        {/* Transfers Table */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Transfer No.
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    From
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Products
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Total Value
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {loading ? (
                  <tr>
                    <td colSpan={8} className="px-6 py-4 text-center text-gray-500">
                      Loading transfers...
                    </td>
                  </tr>
                ) : filteredTransfers.length > 0 ? (
                  filteredTransfers.map((transfer) => (
                    <>
                      <tr key={transfer.transfer_header_id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                          <button
                            onClick={() =>
                              setExpandedTransfer(
                                expandedTransfer === transfer.transfer_header_id ? null : transfer.transfer_header_id,
                              )
                            }
                            className="flex items-center gap-2 hover:underline"
                          >
                            TR-{transfer.transfer_header_id}
                            {expandedTransfer === transfer.transfer_header_id ? (
                              <ChevronUp className="h-4 w-4" />
                            ) : (
                              <ChevronDown className="h-4 w-4" />
                            )}
                          </button>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          <div className="flex flex-col">
                            <span className="font-medium">
                              {new Date(transfer.date).toLocaleDateString()}
                            </span>
                            <span className="text-xs text-gray-500">
                              {new Date(transfer.date).toLocaleTimeString()}
                            </span>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          {transfer.source_location_name}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          {transfer.destination_location_name}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="flex items-center space-x-2">
                          <span
                            className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                              transfer.status === "New"
                                ? "bg-blue-100 text-blue-800"
                                : transfer.status === "In Storage"
                                  ? "bg-yellow-100 text-yellow-800"
                                    : transfer.status === "Transferring"
                                      ? "bg-orange-100 text-orange-800"
                                    : transfer.status === "Completed"
                                      ? "bg-green-100 text-green-800"
                                      : transfer.status === "Cancelled"
                                        ? "bg-red-100 text-red-800"
                                      : "bg-gray-100 text-gray-800"
                              }`}
                            >
                              {transfer.status || "New"}
<<<<<<< HEAD
                          </span>
                            <button
                              onClick={() => handleStatusUpdate(transfer.transfer_header_id, transfer.status)}
                              className="text-xs text-blue-600 hover:text-blue-800 underline"
                            >
                              Update
                            </button>
=======
                            </span>
                            <span className="text-xs text-gray-500">
                              Auto-completed
                            </span>
>>>>>>> 687011100542853d6bad6ac9c30c4dfff5304d80
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          <div className="flex flex-col space-y-1">
                            <span className="font-medium">{transfer.total_products || 0} items</span>
                            {transfer.products && transfer.products.length > 0 && (
                              <div className="text-xs text-gray-500 max-w-xs">
                                {transfer.products.slice(0, 3).map((product, idx) => (
                                  <div key={idx} className="truncate flex items-center">
                                    <span className="w-2 h-2 bg-blue-500 rounded-full mr-1"></span>
                                    {product.product_name} ({product.qty})
                                  </div>
                                ))}
                                {transfer.products.length > 3 && (
                                  <div className="text-xs text-gray-400 italic">
                                    +{transfer.products.length - 3} more products
                                  </div>
                                )}
                              </div>
                            )}
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          <div className="flex flex-col">
                            <span className="font-semibold text-green-600">
                              ₱{transfer.total_value ? Number.parseFloat(transfer.total_value).toFixed(2) : "0.00"}
                            </span>
                            <span className="text-xs text-gray-500">
                          {transfer.total_products || 0} items
                            </span>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          <div className="flex items-center space-x-2">
                            <button
                              onClick={() => {
                                setTransferToDelete(transfer)
                                setShowDeleteModal(true)
                              }}
                              className="text-red-600 hover:text-red-800 p-1 rounded"
                              title="Delete Transfer"
                            >
                              <Trash2 className="h-4 w-4" />
                            </button>
                          </div>
                        </td>
                      </tr>

                      {/* Expanded row showing transfer details */}
                      {expandedTransfer === transfer.transfer_header_id && (
                        <tr>
                          <td colSpan={8} className="px-6 py-4 bg-gray-50">
                            <div className="space-y-4">
                              <div className="flex items-center justify-between">
                                <h4 className="font-semibold text-gray-900 flex items-center">
                                  <Package className="h-5 w-5 mr-2 text-blue-600" />
                                  Transferred Products
                                </h4>
                                <div className="flex items-center space-x-4 text-sm text-gray-600">
                                  <span className="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-semibold">
                                    TR-{transfer.transfer_header_id}
                                  </span>
                                  <span>Employee: {transfer.employee_name}</span>
                                  {transfer.delivery_date && (
                                    <span>Delivery: {new Date(transfer.delivery_date).toLocaleDateString()}</span>
                                  )}
                                </div>
                              </div>

                              {transfer.products && transfer.products.length > 0 ? (
                              <div className="overflow-x-auto">
                                  <table className="min-w-full border border-gray-200 rounded-lg overflow-hidden">
                                    <thead className="bg-blue-50">
                                      <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-blue-900 uppercase tracking-wider">
                                          Product Details
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-blue-900 uppercase tracking-wider">
                                          Category
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-blue-900 uppercase tracking-wider">
                                          Brand
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-blue-900 uppercase tracking-wider">
                                          Barcode
                                        </th>
                                        <th className="px-4 py-3 text-center text-xs font-semibold text-blue-900 uppercase tracking-wider">
                                          Quantity
                                        </th>
                                        <th className="px-4 py-3 text-center text-xs font-semibold text-blue-900 uppercase tracking-wider">
                                          Unit Price
                                        </th>
                                        <th className="px-4 py-3 text-center text-xs font-semibold text-blue-900 uppercase tracking-wider">
                                          Total Value
                                        </th>
                                    </tr>
                                  </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                      {transfer.products.map((product, index) => (
                                        <tr key={index} className="hover:bg-gray-50 transition-colors">
                                          <td className="px-4 py-3">
                                            <div className="flex items-center space-x-3">
                                              <div className="flex-shrink-0">
                                                <img
                                                  src={product.image || "/placeholder.svg?height=32&width=32"}
                                                alt={product.product_name}
                                                  className="h-8 w-8 rounded object-cover border border-gray-200"
                                                />
                                              </div>
                                              <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-gray-900 truncate">
                                                  {product.product_name}
                                                </p>
                                                {product.Variation && (
                                                  <p className="text-xs text-gray-500">
                                                    Variation: {product.Variation}
                                                  </p>
                                                )}
                                                {product.description && (
                                                  <p className="text-xs text-gray-400 truncate">
                                                    {product.description}
                                                  </p>
                                                )}
                                              </div>
                                            </div>
                                          </td>
                                          <td className="px-4 py-3 text-sm text-gray-600">
                                            <span className="inline-flex px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                                              {product.category}
                                            </span>
                                          </td>
                                          <td className="px-4 py-3 text-sm text-gray-600">
                                            <span className="inline-flex px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">
                                              {product.brand || "N/A"}
                                            </span>
                                          </td>
                                          <td className="px-4 py-3 text-sm font-mono text-gray-600">
                                            {product.barcode}
                                          </td>
                                          <td className="px-4 py-3 text-sm text-center">
                                            <span className="inline-flex px-3 py-1 text-sm font-semibold bg-green-100 text-green-800 rounded-full">
                                              {product.qty}
                                            </span>
                                          </td>
                                          <td className="px-4 py-3 text-sm text-center text-gray-900">
                                            ₱{Number.parseFloat(product.unit_price || 0).toFixed(2)}
                                          </td>
                                          <td className="px-4 py-3 text-sm text-center">
                                            <span className="font-semibold text-blue-600">
                                            ₱
                                            {(
                                              Number.parseFloat(product.unit_price || 0) *
                                              Number.parseInt(product.qty || 0)
                                            ).toFixed(2)}
                                            </span>
                                          </td>
                                        </tr>
                                      ))}
                                  </tbody>
                                </table>
                              </div>
                              ) : (
                                <div className="text-center py-8 text-gray-500">
                                  <Package className="h-12 w-12 mx-auto text-gray-300 mb-2" />
                                  <p>No products found for this transfer</p>
                                </div>
                              )}

                              <div className="flex justify-between items-center pt-4 border-t border-gray-200">
                                <div className="flex items-center space-x-6 text-sm text-gray-600">
                                  <span className="flex items-center">
                                    <span className="font-medium">Total Items:</span>
                                    <span className="ml-1 bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-semibold">
                                      {transfer.products ? transfer.products.length : 0}
                                </span>
                                  </span>
                                  <span className="flex items-center">
                                    <span className="font-medium">From:</span>
                                    <span className="ml-1 text-blue-600 font-medium">
                                      {transfer.source_location_name}
                                    </span>
                                  </span>
                                  <span className="flex items-center">
                                    <span className="font-medium">To:</span>
                                    <span className="ml-1 text-green-600 font-medium">
                                      {transfer.destination_location_name}
                                    </span>
                                </span>
                              </div>
                                <div className="text-right">
                                  <div className="text-sm text-gray-600">Total Transfer Value</div>
                                  <div className="text-lg font-bold text-blue-600">
                                    ₱
                                    {transfer.total_value ? Number.parseFloat(transfer.total_value).toFixed(2) : "0.00"}
                                  </div>
                                </div>
                              </div>

                              {/* Transfer Notes */}
                            </div>
                          </td>
                        </tr>
                      )}
                    </>
                  ))
                ) : (
                  <tr>
                    <td colSpan={8} className="px-6 py-8 text-center">
                      <div className="flex flex-col items-center space-y-3">
                        <Truck className="h-12 w-12 text-gray-300" />
                        <div className="text-gray-500">
                          <p className="text-lg font-medium">No transfers found</p>
                          <p className="text-sm">Create your first transfer to get started</p>
                        </div>
                      </div>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Delete Confirmation Modal */}
        {showDeleteModal && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg shadow-xl p-6 w-96">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Delete Transfer</h3>
              <p className="text-gray-700 mb-4">
                Are you sure you want to delete transfer TR-{transferToDelete?.transfer_header_id}? This action cannot
                be undone.
              </p>
              <div className="flex justify-end space-x-4">
                <button
                  onClick={() => {
                    setShowDeleteModal(false)
                    setTransferToDelete(null)
                  }}
                  className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  onClick={() => handleDeleteTransfer(transferToDelete.transfer_header_id)}
                  className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md"
                >
                  Delete
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
    )
  }

  // Create Transfer Modal (Steps 1-3)
  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-6">
        <div className="flex items-center text-sm text-gray-600 mb-2">
          <span>Inventory Management</span>
          <div className="mx-2">{">"}</div>
          <span>Inventory Transfer</span>
          <div className="mx-2">{">"}</div>
          <span className="text-blue-600">Create Transfer</span>
        </div>
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold text-gray-900">
            Create Transfer <span className="text-sm font-normal text-red-500">*Required</span>
          </h1>
          <button
            onClick={() => setShowCreateModal(false)}
            className="text-gray-400 hover:text-gray-600 flex items-center gap-2"
          >
            <X className="h-5 w-5" />
            <span>Back to Transfers</span>
          </button>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-sm border border-gray-200">
        <div className="p-6 space-y-6">
          {/* Step 1: Transfer Stores */}
          {currentStep === 1 && (
            <div>
              <h4 className="text-xl font-semibold text-gray-900 mb-6">
                <span className="bg-gray-900 text-white rounded-full w-6 h-6 inline-flex items-center justify-center text-sm mr-2">
                  1
                </span>
                Transfer stores
              </h4>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Original Store*</label>
                  <select
                    value={storeData.originalStore}
                    onChange={(e) => setStoreData((prev) => ({ ...prev, originalStore: e.target.value }))}
                    disabled={storeData.storesConfirmed}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Select Original Store</option>
                    {locations.map((loc) => (
                      <option key={loc.location_id} value={loc.location_name}>
                        {loc.location_name}
                      </option>
                    ))}
                  </select>
      </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Destination Store*</label>
                  <select
                    value={storeData.destinationStore}
                    onChange={(e) => setStoreData((prev) => ({ ...prev, destinationStore: e.target.value }))}
                    disabled={storeData.storesConfirmed}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Select Destination Store</option>
                    {locations.map((loc) => (
                      <option key={loc.location_id} value={loc.location_name}>
                        {loc.location_name}
                      </option>
                    ))}
                  </select>
    </div>
              </div>
              {!storeData.storesConfirmed ? (
                <div className="text-center">
                  <button
                    onClick={handleConfirmStores}
                    className="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-md font-medium"
                  >
                    Confirm transfer stores
                  </button>
                </div>
              ) : (
                <div className="text-center mt-2">
                  <button
                    onClick={() => setStoreData((prev) => ({ ...prev, storesConfirmed: false }))}
                    className="text-blue-600 underline text-sm"
                  >
                    Edit selected stores
                  </button>
                </div>
              )}
            </div>
          )}

          {/* Step 2: Transfer Information */}
          {currentStep === 2 && (
            <div>
              <h4 className="text-xl font-semibold text-gray-900 mb-6">
                <span className="bg-gray-900 text-white rounded-full w-6 h-6 inline-flex items-center justify-center text-sm mr-2">
                  2
                </span>
                Transfer information
              </h4>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Transferred by (Original Store)*
                  </label>
                  <select
                    value={transferInfo.transferredBy}
                    onChange={(e) => setTransferInfo((prev) => ({ ...prev, transferredBy: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Select staff member</option>
                    {staff.map((member) => (
                      <option key={member.emp_id} value={member.name}>
                        {member.name}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Received by (Destination Store)
                  </label>
                  <select
                    value={transferInfo.receivedBy}
                    onChange={(e) => setTransferInfo((prev) => ({ ...prev, receivedBy: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Select staff member</option>
                    {staff.map((member) => (
                      <option key={member.emp_id} value={member.name}>
                        {member.name}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Delivery Date</label>
                  <input
                    type="date"
                    value={transferInfo.deliveryDate}
                    onChange={(e) => setTransferInfo((prev) => ({ ...prev, deliveryDate: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
              <div className="flex justify-center space-x-3 mt-4">
                <button
                  onClick={() => setCurrentStep(1)}
                  className="px-6 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                >
                  Back
                </button>
                <button
                  onClick={handleNextToProducts}
                  className="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md"
                >
                  Next: Select Products
                </button>
              </div>
            </div>
          )}

          {/* Step 3: Transfer Products */}
          {currentStep === 3 && !showProductSelection && (
            <div>
              <h4 className="text-xl font-semibold text-gray-900 mb-6">
                <span className="bg-gray-900 text-white rounded-full w-6 h-6 inline-flex items-center justify-center text-sm mr-2">
                  3
                </span>
                Transfer Products*
              </h4>
              {selectedProducts.length === 0 ? (
                <div className="text-center py-12">
                  <div className="mb-4">
                    <Package className="h-16 w-16 text-gray-300 mx-auto" />
                  </div>
                  <button
                    onClick={() => setShowProductSelection(true)}
                    className="text-blue-600 hover:text-blue-800 text-lg flex items-center justify-center space-x-2 mx-auto border-2 border-dashed border-blue-300 px-6 py-3 rounded-lg"
                  >
                    <Package className="h-5 w-5" />
                    <span>Select Transfer Products</span>
                  </button>
                </div>
              ) : (
                <div>
                  <div className="overflow-x-auto mb-4 max-h-96 overflow-y-auto">
                    <table className="w-full border-collapse border border-gray-300 text-sm">
                      <thead className="bg-gray-50">
                        <tr>
                          <th className="border border-gray-300 px-2 py-1 text-center text-xs font-medium text-gray-700">
                            Transfer Qty
                          </th>
                          <th className="border border-gray-300 px-2 py-1 text-left text-xs font-medium text-gray-700">
                            Product
                          </th>
                          <th className="border border-gray-300 px-2 py-1 text-left text-xs font-medium text-gray-700">
                            Variation
                          </th>
                          <th className="border border-gray-300 px-2 py-1 text-left text-xs font-medium text-gray-700">
                            Category
                          </th>
                          <th className="border border-gray-300 px-2 py-1 text-left text-xs font-medium text-gray-700">
                            Brand
                          </th>
                          <th className="border border-gray-300 px-2 py-1 text-left text-xs font-medium text-gray-700">
                            Barcode
                          </th>
                          <th className="border border-gray-300 px-2 py-1 text-center text-xs font-medium text-gray-700">
                            Available Qty
                          </th>
                          <th className="border border-gray-300 px-2 py-1 text-center text-xs font-medium text-gray-700">
                            Unit Price
                          </th>
                          <th className="border border-gray-300 px-2 py-1 text-center text-xs font-medium text-gray-700">
                            Action
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        {selectedProducts.map((product) => (
                          <tr key={product.product_id} className="hover:bg-gray-50">
                            <td className="border border-gray-300 px-2 py-1 text-center">
                              <div className="flex flex-col items-center">
                                <input
                                  type="number"
                                  min="0"
                                  max={product.quantity || 999}
                                  value={product.transfer_quantity}
                                  onChange={(e) => updateTransferQuantity(product.product_id, e.target.value)}
                                  className={`w-20 px-2 py-1 border rounded text-center focus:outline-none focus:ring-2 ${
                                    product.transfer_quantity > (product.quantity || 0) 
                                      ? 'border-red-500 focus:ring-red-500' 
                                      : 'border-red-300 focus:ring-red-500'
                                  }`}
                                />
                                {product.transfer_quantity > 0 && (
                                  <div className="text-xs mt-1">
                                    <span className={`${
                                      product.transfer_quantity <= (product.quantity || 0) 
                                        ? 'text-green-600' 
                                        : 'text-red-600'
                                    }`}>
                                      {product.transfer_quantity} / {product.quantity || 0}
                                    </span>
                                  </div>
                                )}
                              </div>
                            </td>
                            <td className="border border-gray-300 px-2 py-1">
                              <div className="flex items-center space-x-3">
                                <img
                                  src={product.image || "/placeholder.svg?height=32&width=32"}
                                  alt={product.product_name}
                                  className="h-8 w-8 rounded object-cover"
                                />
                                <span className="text-sm font-medium">{product.product_name}</span>
                              </div>
                            </td>
                            <td className="border border-gray-300 px-2 py-1 text-sm">{product.variation || "-"}</td>
                            <td className="border border-gray-300 px-2 py-1 text-sm">{product.category}</td>
                            <td className="border border-gray-300 px-2 py-1 text-sm">{product.brand || "-"}</td>
                            <td className="border border-gray-300 px-2 py-1 text-sm font-mono">{product.barcode}</td>
                            <td className="border border-gray-300 px-2 py-1 text-sm text-center font-semibold">
                              {product.quantity || 0}
                            </td>
                            <td className="border border-gray-300 px-2 py-1 text-sm text-center">
                              ₱{Number.parseFloat(product.unit_price || 0).toFixed(2)}
                            </td>
                            <td className="border border-gray-300 px-2 py-1 text-center">
                              <button
                                onClick={() => removeProduct(product.product_id)}
                                className="text-red-500 hover:text-red-700"
                              >
                                <X className="h-4 w-4" />
                              </button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>

                  <div className="flex justify-between">
                    <button
                      onClick={() => setShowProductSelection(true)}
                      className="text-blue-600 hover:text-blue-800 text-sm border border-blue-300 px-4 py-2 rounded"
                    >
                      <Package className="h-4 w-4 inline mr-2" />
                      Select Transfer Products
                    </button>
                    <div className="flex space-x-4">
                      <button
                        onClick={() => setCurrentStep(2)}
                        className="px-6 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                      >
                        Back
                      </button>
                      <button
                        onClick={handleTransferSubmit}
                        disabled={loading || selectedProducts.filter((p) => p.transfer_quantity > 0).length === 0}
                        className="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        {loading ? "Creating..." : "Create Transfer"}
                      </button>
                    </div>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Product Selection View */}
          {currentStep === 3 && showProductSelection && (
            <div>
              <h4 className="text-xl font-semibold text-gray-900 mb-6">
                <span className="bg-gray-900 text-white rounded-full w-6 h-6 inline-flex items-center justify-center text-sm mr-2">
                  3
                </span>
                Select Transfer Products from Warehouse ({availableProducts.length} products available)
              </h4>
              {/* Search and Filters */}
              <div className="flex items-center gap-4 mb-6">
                <div className="relative flex-1">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                  <input
                    type="text"
                    placeholder="Search by Product Name/SKU/Barcode"
                    value={productSearchTerm}
                    onChange={(e) => setProductSearchTerm(e.target.value)}
                    className="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <select
                  value={selectedCategory}
                  onChange={(e) => setSelectedCategory(e.target.value)}
                  className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="All Product Category">All Product Category</option>
                  {categories.map((category) => (
                    <option key={category} value={category}>
                      {category}
                    </option>
                  ))}
                </select>
                <select
                  value={selectedSupplier}
                  onChange={(e) => setSelectedSupplier(e.target.value)}
                  className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="All Suppliers">All Suppliers</option>
                  {suppliers.map((supplier) => (
                    <option key={supplier} value={supplier}>
                      {supplier}
                    </option>
                  ))}
                </select>
              </div>
              {/* Products Table */}
              <div className="overflow-x-auto max-h-96 overflow-y-auto mb-4">
                <table className="w-full border-collapse border border-gray-300">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="border border-gray-300 px-4 py-2 text-center">
                        <input
                          type="checkbox"
                          onChange={(e) => {
                            if (e.target.checked) {
                              setCheckedProducts(filteredProducts.map((p) => p.product_id))
                            } else {
                              setCheckedProducts([])
                            }
                          }}
                          checked={checkedProducts.length === filteredProducts.length && filteredProducts.length > 0}
                        />
                      </th>
                      <th className="border border-gray-300 px-4 py-2 text-left text-sm font-medium text-gray-700">
                        Product
                      </th>
                      <th className="border border-gray-300 px-4 py-2 text-left text-sm font-medium text-gray-700">
                        Variation
                      </th>
                      <th className="border border-gray-300 px-4 py-2 text-left text-sm font-medium text-gray-700">
                        Category
                      </th>
                      <th className="border border-gray-300 px-4 py-2 text-left text-sm font-medium text-gray-700">
                        Brand
                      </th>
                      <th className="border border-gray-300 px-4 py-2 text-left text-sm font-medium text-gray-700">
                        Supplier
                      </th>
                      <th className="border border-gray-300 px-4 py-2 text-left text-sm font-medium text-gray-700">
                        Barcode
                      </th>
                      <th className="border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700">
                        Available Qty
                      </th>
                      <th className="border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700">
                        Unit Price
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredProducts.map((product) => (
                      <tr key={product.product_id} className="hover:bg-gray-50">
                        <td className="border border-gray-300 px-4 py-2 text-center">
                          <input
                            type="checkbox"
                            checked={checkedProducts.includes(product.product_id)}
                            onChange={(e) => handleProductCheck(product.product_id, e.target.checked)}
                          />
                        </td>
                        <td className="border border-gray-300 px-4 py-2">
                          <span className="text-sm font-medium">{product.product_name}</span>
                        </td>
                        <td className="border border-gray-300 px-4 py-2 text-sm">{product.variation || "-"}</td>
                        <td className="border border-gray-300 px-4 py-2 text-sm">{product.category}</td>
                        <td className="border border-gray-300 px-4 py-2 text-sm">{product.brand || "-"}</td>
                        <td className="border border-gray-300 px-4 py-2 text-sm">{product.supplier_name || "-"}</td>
                        <td className="border border-gray-300 px-4 py-2 text-sm font-mono">{product.barcode}</td>
                        <td className="border border-gray-300 px-4 py-2 text-sm text-center font-semibold">
                          {product.quantity || 0}
                        </td>
                        <td className="border border-gray-300 px-4 py-2 text-sm text-center">
                          ₱{Number.parseFloat(product.unit_price || 0).toFixed(2)}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              {/* Action Buttons */}
              <div className="flex justify-between items-center">
                <button className="text-blue-600 hover:text-blue-800 text-sm border border-blue-300 px-4 py-2 rounded">
                  View Selected Products({checkedProducts.length}/500)
                </button>
                <div className="flex space-x-4">
                  <button
                    onClick={() => setShowProductSelection(false)}
                    className="px-6 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                  >
                    Back
                  </button>
                  <button
                    onClick={handleSelectProducts}
                    disabled={checkedProducts.length === 0}
                    className="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Select ({checkedProducts.length} products)
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
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
  )
}
export default InventoryTransfer; 