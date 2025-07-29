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
  CheckCircle,
} from "lucide-react";


function InventoryTransfer() {
  const [transfers, setTransfers] = useState([])
  const [transferLogs, setTransferLogs] = useState([])
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [currentStep, setCurrentStep] = useState(1)
  const [loading, setLoading] = useState(false)
  const [searchTerm, setSearchTerm] = useState("")
  const [dateFilter, setDateFilter] = useState("")
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

  const API_BASE_URL = "http://localhost/Enguio_Project/Api/backend_mysqli.php"

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
        
        // Process and enhance transfer data
        const processedTransfers = response.data.map(transfer => ({
          ...transfer,
          // Ensure products array exists and has proper structure
          products: transfer.products || [],
          // Calculate totals if not provided
          total_products: transfer.total_products || (transfer.products ? transfer.products.length : 0),
          total_value: transfer.total_value || (transfer.products ? 
            transfer.products.reduce((sum, product) => sum + (Number.parseFloat(product.srp || product.unit_price || 0) * Number.parseInt(product.qty || 0)), 0) : 0
          ),
          // Format status for display
          display_status: transfer.status === "approved" ? "Completed" : 
                         transfer.status === "pending" ? "Pending Review" : 
                         transfer.status === "rejected" ? "Rejected" : 
                         transfer.status || "Completed"
        }))
        
        console.log("🔄 Processed transfers:", processedTransfers)
        setTransfers(processedTransfers)
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

  const loadTransferLogs = async () => {
    try {
      const response = await handleApiCall("get_transfer_log")
      console.log("📊 Transfer Logs Loaded from API:", response)

      if (response.success && Array.isArray(response.data)) {
        console.log("✅ Number of transfer logs received:", response.data.length)
        setTransferLogs(response.data)
      } else {
        console.warn("⚠️ No transfer logs found or invalid format")
        setTransferLogs([])
      }
    } catch (error) {
      console.error("❌ Error loading transfer logs:", error)
      setTransferLogs([])
    }
  }

  const loadAvailableProducts = async (sourceLocationId = null) => {
    try {
      console.log("Loading products from source location...")
      const response = await handleApiCall("get_products", sourceLocationId ? { location_id: sourceLocationId } : {})
      if (response.success && Array.isArray(response.data)) {
        console.log("✅ Loaded products from source location:", response.data.length)
        setAvailableProducts(response.data)
      } else {
        console.warn("⚠️ No products found from API")
        setAvailableProducts([])
      }
    } catch (error) {
      console.error("Error loading products:", error)
      toast.error("Failed to load products from source location")
      setAvailableProducts([])
    }
  }

  // Load locations
  const loadLocations = async () => {
    try {
      const res = await handleApiCall("get_locations")
      console.log("📦 API Response from get_locations:", res)
      if (res.success && Array.isArray(res.data)) {
        setLocations(res.data)
        
        // Validate location mapping
        console.log("🔍 Location Mapping Validation:")
        res.data.forEach(loc => {
          console.log(`Location: ${loc.location_name} (ID: ${loc.location_id})`)
        })
        
        // Check for convenience store specifically
        const convenienceStore = res.data.find(loc => 
          loc.location_name === "Convenience"
        )
        if (convenienceStore) {
          console.log("✅ Found Convenience Store:", convenienceStore.location_name, "(ID:", convenienceStore.location_id, ")")
        } else {
          console.warn("⚠️ No convenience store found in locations")
        }
        
        // Check for warehouse specifically
        const warehouse = res.data.find(loc => 
          loc.location_name.toLowerCase().includes('warehouse')
        )
        if (warehouse) {
          console.log("✅ Found Warehouse:", warehouse.location_name, "(ID:", warehouse.location_id, ")")
        } else {
          console.warn("⚠️ No warehouse found in locations")
        }
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

  // Function to calculate total products transferred
  const calculateTotalProductsTransferred = () => {
    let totalProducts = 0;
    
    // Calculate from transfers array
    transfers.forEach(transfer => {
      if (transfer.products && Array.isArray(transfer.products)) {
        transfer.products.forEach(product => {
          totalProducts += Number.parseInt(product.qty || 0);
        });
      } else if (transfer.total_products) {
        totalProducts += Number.parseInt(transfer.total_products);
      }
    });
    
    // Also calculate from transfer logs for more accurate count
    transferLogs.forEach(log => {
      totalProducts += Number.parseInt(log.quantity || 0);
    });
    
    return totalProducts;
  }

  // Function to get transfer statistics
  const getTransferStatistics = () => {
    const totalProducts = calculateTotalProductsTransferred();
    const totalTransfers = transfers.length;
    const totalLogs = transferLogs.length;
    
    return {
      totalProducts,
      totalTransfers,
      totalLogs,
      averageProductsPerTransfer: totalTransfers > 0 ? Math.round(totalProducts / totalTransfers) : 0
    };
  }

  // Function to track transfer records per session
  const [sessionTransfers, setSessionTransfers] = useState(0);
  const [sessionStartTime, setSessionStartTime] = useState(new Date());

  // Function to get session transfer statistics
  const getSessionTransferStats = () => {
    const currentTime = new Date();
    const sessionDuration = Math.round((currentTime - sessionStartTime) / 1000 / 60); // in minutes
    
    return {
      sessionTransfers,
      sessionDuration,
      transfersPerMinute: sessionDuration > 0 ? (sessionTransfers / sessionDuration).toFixed(2) : 0
    };
  };

  // Function to increment session transfer count
  const incrementSessionTransfers = () => {
    setSessionTransfers(prev => prev + 1);
  };

  // Function to reset session
  const resetSession = () => {
    setSessionTransfers(0);
    setSessionStartTime(new Date());
  };



  useEffect(() => {
    loadTransfers()
    loadTransferLogs()
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

    // Enhanced validation for convenience store transfers
    const isConvenienceStoreTransfer = storeData.destinationStore === "Convenience";
    const convenience = locations.find(loc => loc.location_name === "Convenience");
    
    if (isConvenienceStoreTransfer) {
      console.log("🏪 Special handling for Warehouse → Convenience Store transfer")
      
      // Validate that we have sufficient quantities
      const insufficientProducts = productsToTransfer.filter(p => p.transfer_quantity > p.quantity)
      if (insufficientProducts.length > 0) {
        const productNames = insufficientProducts.map(p => p.product_name).join(', ')
        toast.error(`Insufficient quantity for: ${productNames}`)
        return
      }
    }

    setLoading(true)
    try {
      // Find location IDs
      const sourceLocation = locations.find((loc) => loc.location_name === storeData.originalStore)
      const destinationLocation = locations.find((loc) => loc.location_name === storeData.destinationStore)

      console.log("🔍 Location Debug Info:")
      console.log("Available locations:", locations.map(loc => `${loc.location_name} (ID: ${loc.location_id})`))
      console.log("Selected original store:", storeData.originalStore)
      console.log("Selected destination store:", storeData.destinationStore)
      console.log("Found source location:", sourceLocation)
      console.log("Found destination location:", destinationLocation)

      if (!sourceLocation || !destinationLocation) {
        console.error("❌ Location validation failed:")
        console.error("Source location found:", !!sourceLocation)
        console.error("Destination location found:", !!destinationLocation)
        toast.error("Invalid location selection")
        setLoading(false)
        return
      }

      // Validate that we're not transferring to the same location
      if (sourceLocation.location_id === destinationLocation.location_id) {
        console.error("❌ Same location transfer detected")
        toast.error("Source and destination cannot be the same")
        setLoading(false)
        return
      }

      // Find employee ID
      const transferEmployee = staff.find((emp) => emp.name === transferInfo.transferredBy)
      if (!transferEmployee) {
        console.error("❌ Employee not found:", transferInfo.transferredBy)
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

      console.log("📦 Transfer Data Validation:")
      console.log("Source Location ID:", transferData.source_location_id, "Name:", sourceLocation.location_name)
      console.log("Destination Location ID:", transferData.destination_location_id, "Name:", destinationLocation.location_name)
      console.log("Employee ID:", transferData.employee_id, "Name:", transferEmployee.name)
      console.log("Products to transfer:", productsToTransfer.map(p => `${p.product_name} (${p.transfer_quantity} qty)`))
      
      // Double-check convenience store transfer
      if (isConvenienceStoreTransfer) {
        console.log("🏪 Convenience Store Transfer Validation:")
        console.log("Is convenience store transfer:", isConvenienceStoreTransfer)
        console.log("Destination location name:", destinationLocation.location_name)
        console.log("Destination location ID:", destinationLocation.location_id)
        console.log("Expected destination should be convenience store")
      }

      console.log("📦 Sending transfer data:", transferData)
      console.log("📍 Transfer Direction: FROM", storeData.originalStore, "TO", storeData.destinationStore)
      console.log("📦 Products being transferred:", productsToTransfer.map(p => `${p.product_name} (${p.transfer_quantity} qty)`))
      
      // Special confirmation for convenience store transfers
      if (isConvenienceStoreTransfer) {
        console.log("🏪 Confirming convenience store transfer...")
        toast.info("🔄 Processing transfer to convenience store...")
      }
      
      const response = await handleApiCall("create_transfer", transferData)
      console.log("📥 Transfer creation response:", response)

      if (response.success) {
        const transferredCount = response.products_transferred || 0;
        
        console.log("✅ Transfer successful!")
        console.log("Transfer ID:", response.transfer_id)
        console.log("Products transferred:", transferredCount)
        console.log("Source location:", response.source_location)
        console.log("Destination location:", response.destination_location)
        
        // Enhanced success message based on transfer type
        if (isConvenienceStoreTransfer) {
          toast.success(`✅ Transfer completed! ${transferredCount} product(s) moved FROM ${storeData.originalStore} TO ${storeData.destinationStore}. Products are now available in the convenience store inventory.`)
        } else {
          toast.success(`✅ Transfer completed! ${transferredCount} product(s) moved FROM ${storeData.originalStore} TO ${storeData.destinationStore}.`)
        }
        
        console.log("✅ Transfer created with ID:", response.transfer_id)

        // Reset form
        setShowCreateModal(false)
        setCurrentStep(1)
        setStoreData({ originalStore: "", destinationStore: "", storesConfirmed: false })
        setTransferInfo({ transferredBy: "", receivedBy: "", deliveryDate: "" })
        setSelectedProducts([])
        setCheckedProducts([])

        // Increment session transfer count
        incrementSessionTransfers();
        
        // Reload transfers to show the new one
        console.log("🔄 Reloading transfers...")
        await loadTransfers()
        
        // Reload transfer logs to show the new entries
        console.log("🔄 Reloading transfer logs...")
        await loadTransferLogs()
        
        // Force reload of available products to reflect the transfer
        if (sourceLocation) {
          console.log("🔄 Reloading source location products...")
          await loadAvailableProducts(sourceLocation.location_id)
        }
        
        // Special notification for convenience store transfers
        if (isConvenienceStoreTransfer) {
          setTimeout(() => {
            toast.info("🏪 You can now view the transferred products in the Convenience Store inventory page.")
          }, 2000)
        }
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
    
    // Find the source location ID
    const sourceLocation = locations.find((loc) => loc.location_name === storeData.originalStore)
    if (!sourceLocation) {
      toast.error("Invalid source location selection")
      return
    }
    
    setStoreData((prev) => ({ ...prev, storesConfirmed: true }))
    setCurrentStep(2)
    
    // Load products from the selected source location
    loadAvailableProducts(sourceLocation.location_id)
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
    // Since transfers are now approved immediately, we'll just show a message
    toast.info("Transfer status: " + (currentStatus === "approved" ? "Completed" : currentStatus) + " - Products have been transferred to destination store")
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
    (transfer) => {
      const matchesSearch = 
        transfer.transfer_header_id?.toString().toLowerCase().includes(searchTerm.toLowerCase()) ||
        transfer.source_location_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        transfer.destination_location_name?.toLowerCase().includes(searchTerm.toLowerCase());
      
      const matchesDate = !dateFilter || 
        (transfer.date && new Date(transfer.date).toISOString().split('T')[0] === dateFilter);
      
      return matchesSearch && matchesDate;
    }
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
              <div className="relative">
                <input
                  type="date"
                  placeholder="Filter by date..."
                  value={dateFilter}
                  onChange={(e) => setDateFilter(e.target.value)}
                  className="px-4 py-2 w-48 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                {dateFilter && (
                  <button
                    onClick={() => setDateFilter("")}
                    className="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    <X className="h-4 w-4" />
                  </button>
                )}
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
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
              <div className="text-3xl font-bold text-blue-600">
                {calculateTotalProductsTransferred()}
              </div>
              <div className="text-sm text-gray-600 mt-1">Total Products Transferred</div>
              <div className="text-xs text-blue-500 mt-1">
                {getTransferStatistics().averageProductsPerTransfer} avg per transfer
              </div>
            </div>
            <div className="text-center p-4 bg-purple-50 rounded-lg border border-purple-200">
              <div className="text-3xl font-bold text-purple-600">
                {transfers.length}
              </div>
              <div className="text-sm text-gray-600 mt-1">Total Transfer Records</div>
              <div className="text-xs text-purple-500 mt-1">
                {getSessionTransferStats().sessionTransfers} this session ({getSessionTransferStats().sessionDuration} min)
              </div>
            </div>
          </div>
          
          {/* Session Controls */}
          <div className="mt-4 flex justify-center">
            <button
              onClick={resetSession}
              className="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm flex items-center gap-2"
            >
              <span>🔄</span>
              Reset Session Counter
            </button>
          </div>
        </div>
          
          {/* Convenience Store Transfer Summary */}
          {transfers.filter((t) => 
            t.destination_location_name && 
            t.destination_location_name.toLowerCase().includes('convenience')
          ).length > 0 && (
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
              <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                <div className="flex items-center mb-2">
                  <Package className="h-5 w-5 text-green-600 mr-2" />
                  <span className="font-medium text-green-900">Convenience Store Transfer Summary</span>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                  <div>
                    <span className="font-medium text-green-700">Total Transfers:</span>
                    <span className="ml-2 text-green-600">
                      {transfers.filter((t) => 
                        t.destination_location_name && 
                        t.destination_location_name.toLowerCase().includes('convenience')
                      ).length}
                    </span>
                  </div>
                  <div>
                    <span className="font-medium text-green-700">Products Moved:</span>
                    <span className="ml-2 text-green-600">
                      {transfers.filter((t) => 
                        t.destination_location_name && 
                        t.destination_location_name.toLowerCase().includes('convenience')
                      ).reduce((sum, transfer) => sum + (transfer.total_products || 0), 0)}
                    </span>
                  </div>
                  <div>
                    <span className="font-medium text-green-700">Total Value:</span>
                    <span className="ml-2 text-green-600">
                      ₱{transfers.filter((t) => 
                        t.destination_location_name && 
                        t.destination_location_name.toLowerCase().includes('convenience')
                      ).reduce((sum, transfer) => sum + (Number.parseFloat(transfer.total_value) || 0), 0).toFixed(2)}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          )}



        {/* Transfer Log Table */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
          <div className="p-4 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900">Transfer Log Details</h3>
            <p className="text-sm text-gray-600 mt-1">Detailed log of all product transfers with individual item tracking</p>
          </div>
          <div className="overflow-x-auto max-h-96">
            <table className="w-full min-w-max">
              <thead className="bg-indigo-50 border-b border-gray-200 sticky top-0 z-10">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-indigo-900 uppercase tracking-wider">
                    Transfer ID
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-indigo-900 uppercase tracking-wider">
                    Product
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-indigo-900 uppercase tracking-wider">
                    From Location
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-indigo-900 uppercase tracking-wider">
                    To Location
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-indigo-900 uppercase tracking-wider">
                    Quantity
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-indigo-900 uppercase tracking-wider">
                    Transfer Date
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-indigo-900 uppercase tracking-wider">
                    Logged At
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {loading ? (
                  <tr>
                    <td colSpan={7} className="px-6 py-4 text-center text-gray-500">
                      Loading transfer logs...
                    </td>
                  </tr>
                ) : transferLogs.length > 0 ? (
                  transferLogs.map((log, index) => (
                    <tr key={index} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                        TR-{log.transfer_id}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div className="flex flex-col">
                          <span className="font-medium">{log.product_name || `Product ID: ${log.product_id}`}</span>
                          <span className="text-xs text-gray-500">ID: {log.product_id}</span>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span className="inline-flex px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                          {log.from_location}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span className="inline-flex px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                          {log.to_location}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span className="inline-flex px-3 py-1 text-sm font-semibold bg-blue-100 text-blue-800 rounded-full">
                          {log.quantity} units
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div className="flex flex-col">
                          <span className="font-medium">
                            {new Date(log.transfer_date).toLocaleDateString()}
                          </span>
                          <span className="text-xs text-gray-500">
                            {new Date(log.transfer_date).toLocaleTimeString()}
                          </span>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div className="flex flex-col">
                          <span className="font-medium">
                            {new Date(log.created_at).toLocaleDateString()}
                          </span>
                          <span className="text-xs text-gray-400">
                            {new Date(log.created_at).toLocaleTimeString()}
                          </span>
                        </div>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={7} className="px-6 py-8 text-center">
                      <div className="flex flex-col items-center space-y-3">
                        <div className="h-12 w-12 bg-indigo-100 rounded-full flex items-center justify-center">
                          <span className="text-indigo-600 text-xl">📊</span>
                        </div>
                        <div className="text-gray-500">
                          <p className="text-lg font-medium">No transfer logs found</p>
                          <p className="text-sm">Transfer logs will appear here after transfers are created</p>
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
          <div className="fixed inset-0 backdrop-blur-md flex items-center justify-center z-50">
            <div className="bg-transparent backdrop-blur-sm rounded-lg shadow-xl p-6 w-96 border-2 border-gray-400">
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
              
              {/* Transfer Direction Guide */}
              <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div className="flex items-center mb-2">
                  <Truck className="h-5 w-5 text-blue-600 mr-2" />
                  <span className="font-medium text-blue-900">Transfer Direction Guide</span>
                </div>
                <div className="text-sm text-blue-700 space-y-1">
                  <p>• <strong>Warehouse → Convenience Store:</strong> Move products from warehouse to convenience store for retail</p>
                  <p>• <strong>Warehouse → Pharmacy:</strong> Move products from warehouse to pharmacy for prescription sales</p>
                  <p>• <strong>Convenience Store → Pharmacy:</strong> Move products between retail locations</p>
                </div>
              </div>
              
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
                  
                  {/* Convenience Store Indicator */}
                  {storeData.destinationStore && storeData.destinationStore.toLowerCase().includes('convenience') && (
                    <div className="mt-2 p-2 bg-green-50 border border-green-200 rounded text-sm text-green-700">
                      <div className="flex items-center">
                        <CheckCircle className="h-4 w-4 mr-1" />
                        <span>Products will be available for retail sales in convenience store</span>
                      </div>
                    </div>
                  )}
                </div>
              </div>
              
              {/* Transfer Preview */}
              {storeData.originalStore && storeData.destinationStore && storeData.originalStore !== storeData.destinationStore && (
                <div className="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                      <div className="text-center">
                        <div className="font-medium text-gray-900">{storeData.originalStore}</div>
                        <div className="text-xs text-gray-500">Source</div>
                      </div>
                      <div className="flex items-center">
                        <Truck className="h-5 w-5 text-blue-600" />
                        <div className="mx-2 text-gray-400">→</div>
                      </div>
                      <div className="text-center">
                        <div className="font-medium text-gray-900">{storeData.destinationStore}</div>
                        <div className="text-xs text-gray-500">Destination</div>
                      </div>
                    </div>
                    {storeData.destinationStore.toLowerCase().includes('convenience') && (
                      <div className="flex items-center text-green-600">
                        <Package className="h-4 w-4 mr-1" />
                        <span className="text-sm font-medium">Retail Ready</span>
                      </div>
                    )}
                  </div>
                </div>
              )}
              
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
              
              {/* Convenience Store Transfer Tips */}
              {storeData.destinationStore && storeData.destinationStore.toLowerCase().includes('convenience') && (
                <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                  <div className="flex items-center mb-2">
                    <Package className="h-5 w-5 text-green-600 mr-2" />
                    <span className="font-medium text-green-900">Convenience Store Transfer Tips</span>
                  </div>
                  <div className="text-sm text-green-700 space-y-1">
                    <p>• Select products that are suitable for retail sales</p>
                    <p>• Ensure quantities are appropriate for convenience store demand</p>
                    <p>• Products will be immediately available for POS transactions</p>
                    <p>• Check expiration dates for perishable items</p>
                  </div>
                </div>
              )}
              
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
                    <span>Select Products from {storeData.originalStore}</span>
                  </button>
                  
                  {/* Additional guidance for convenience store */}
                  {storeData.destinationStore && storeData.destinationStore.toLowerCase().includes('convenience') && (
                    <div className="mt-4 text-sm text-gray-600">
                      <p>💡 Tip: Choose products that customers typically buy in convenience stores</p>
                    </div>
                  )}
                </div>
              ) : (
                <div>
                  {/* Transfer Summary */}
                  <div className="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-700">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center">
                        <CheckCircle className="h-4 w-4 mr-2" />
                        <span>Products will be transferred to {storeData.destinationStore} for retail sales</span>
                      </div>
                      <div className="text-right">
                        <div className="text-sm font-medium text-blue-900">
                          {selectedProducts.filter(p => p.transfer_quantity > 0).length} products selected for transfer
                        </div>
                        <div className="text-xs text-blue-700">
                          Total quantity: {selectedProducts.reduce((sum, p) => sum + (p.transfer_quantity || 0), 0)} items
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <div className="overflow-x-auto max-h-96">
                    <table className="w-full min-w-max border-collapse border border-gray-300 text-sm">
                      <thead className="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                        <tr>
                          <th className="border border-gray-300 px-2 py-1 text-center text-xs font-medium text-gray-700">
                            Status
                          </th>
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
                            Batch Number
                          </th>
                          <th className="border border-gray-300 px-2 py-1 text-center text-xs font-medium text-gray-700">
                            Available Qty
                          </th>
                                                  <th className="border border-gray-300 px-2 py-1 text-center text-xs font-medium text-gray-700">
                          SRP
                        </th>
                          <th className="border border-gray-300 px-2 py-1 text-center text-xs font-medium text-gray-700">
                            Action
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        {selectedProducts.map((product) => (
                          <tr key={product.product_id} className="hover:bg-gray-50 bg-green-50">
                            <td className="border border-gray-300 px-2 py-1 text-center">
                              <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <CheckCircle className="h-3 w-3 mr-1" />
                                Selected for Transfer
                              </span>
                            </td>
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
                              {product.batch_id || 'N/A'}
                            </td>
                            <td className="border border-gray-300 px-2 py-1 text-sm text-center font-semibold">
                              {product.quantity || 0}
                            </td>
                            <td className="border border-gray-300 px-2 py-1 text-sm text-center">
                              ₱{Number.parseFloat(product.srp || 0).toFixed(2)}
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
                      Select Products from {storeData.originalStore}
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
                Select Transfer Products from {storeData.originalStore} ({availableProducts.length} products available)
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
              <div className="overflow-x-auto max-h-96 mb-4">
                <table className="w-full min-w-max border-collapse border border-gray-300">
                                        <thead className="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
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
                          <th className="border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700">
                            Status
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
                        Batch Number
                      </th>
                      <th className="border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700">
                        Available Qty
                      </th>
                                              <th className="border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700">
                        SRP
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredProducts.map((product) => {
                      const isTransferred = selectedProducts.some(sp => sp.product_id === product.product_id);
                      return (
                      <tr key={product.product_id} className={`hover:bg-gray-50 ${isTransferred ? 'bg-green-50' : ''}`}>
                        <td className="border border-gray-300 px-4 py-2 text-center">
                          <input
                            type="checkbox"
                            checked={checkedProducts.includes(product.product_id)}
                            onChange={(e) => handleProductCheck(product.product_id, e.target.checked)}
                            disabled={isTransferred}
                          />
                        </td>
                        <td className="border border-gray-300 px-4 py-2 text-center">
                          {isTransferred ? (
                            <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                              <CheckCircle className="h-3 w-3 mr-1" />
                              Transferred
                            </span>
                          ) : (
                            <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                              Available
                            </span>
                          )}
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
                          {product.batch_id || 'N/A'}
                        </td>
                        <td className="border border-gray-300 px-4 py-2 text-sm text-center font-semibold">
                          {product.quantity || 0}
                        </td>
                        <td className="border border-gray-300 px-4 py-2 text-sm text-center">
                          ₱{Number.parseFloat(product.srp || 0).toFixed(2)}
                        </td>
                      </tr>
                    );
                    })}
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