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
  MapPin,
  Scan,
  Camera,
  Package,
  User,
  Truck,
  DollarSign,
  Edit,
  Archive,
} from "lucide-react";

// API Configuratio

// API function
async function handleApiCall(action, data = {}) {
  const API_BASE_URL = "http://localhost/Enguio_Project/Api/backend.php";
  const payload = { action, ...data };
  console.log("🚀 API Call Payload:", payload);

  try {
    const response = await fetch(API_BASE_URL, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    });

    const resData = await response.json();
    console.log("✅ API Success Response:", resData);

    if (resData && typeof resData === "object") {
      if (!resData.success) {
        console.warn("⚠️ API responded with failure:", resData.message || resData);
      }
      return resData;
    } else {
      console.warn("⚠️ Unexpected API response format:", resData);
      return {
        success: false,
        message: "Unexpected response format",
        data: resData,
      };
    }
  } catch (error) {
    console.error("❌ API Call Error:", error);
    return {
      success: false,
      message: error.message,
      error: "REQUEST_ERROR",
    };
  }
}

// New function to check if barcode exists
async function checkBarcodeExists(barcode) {
  try {
    const response = await handleApiCall("check_barcode", { barcode });
    return response;
  } catch (error) {
    console.error("Error checking barcode:", error);
    return { success: false, error: error.message };
  }
}

// New function to update product stock with FIFO tracking
async function updateProductStock(productId, newQuantity, batchReference = "", expirationDate = null, unitCost = 0) {
  try {
    const response = await handleApiCall("update_product_stock", { 
      product_id: productId, 
      new_quantity: newQuantity,
      batch_reference: batchReference,
      expiration_date: expirationDate,
      unit_cost: unitCost,
      entry_by: "admin"
    });
    return response;
  } catch (error) {
    console.error("Error updating product stock:", error);
    return { success: false, error: error.message };
  }
}

function Warehouse() {
    // State Management
    const [scannerStatusMessage, setScannerStatusMessage] = useState("");
    const [scanTimeout, setScanTimeout] = useState(null);
  
    const [inventoryData, setInventoryData] = useState([])
    const [suppliersData, setSuppliersData] = useState([])
    const [batchData, setBatchData] = useState([])
    const [brandsData, setBrandsData] = useState([])
    const [categoriesData, setCategoriesData] = useState([])
    const [searchTerm, setSearchTerm] = useState("")
    const [loading, setLoading] = useState(false)
    const [showAddModal, setShowAddModal] = useState(false)
    const [showSupplierModal, setShowSupplierModal] = useState(false)
    const [showEditModal, setShowEditModal] = useState(false)
    const [showEditProductModal, setShowEditProductModal] = useState(false)
    const [showDeleteModal, setShowDeleteModal] = useState(false)
    const [activeTab, setActiveTab] = useState("products")
    const [currentLocation, setCurrentLocation] = useState("warehouse")
    const [scannerActive, setScannerActive] = useState(false)
    const [scannedBarcode, setScannedBarcode] = useState("")
    const [selectedItem, setSelectedItem] = useState(null)
    const [useSameBatch, setUseSameBatch] = useState(true)
    const [showProductModal, setShowProductModal] = useState(false);
    const [selectedProducts, setSelectedProducts] = useState([]);
    
    // New state for barcode scanning modals
    const [showUpdateStockModal, setShowUpdateStockModal] = useState(false);
    const [showNewProductModal, setShowNewProductModal] = useState(false);
    const [existingProduct, setExistingProduct] = useState(null);
    const [newStockQuantity, setNewStockQuantity] = useState("");
    const [showFifoModal, setShowFifoModal] = useState(false);
    const [fifoStockData, setFifoStockData] = useState([]);
    const [selectedProductForFifo, setSelectedProductForFifo] = useState(null);
    

    

    const [newProductForm, setNewProductForm] = useState({
      product_name: "",
      category: "",
      barcode: "",
      description: "",
      unit_price: "",
      brand_id: "",
      brand_search: "",
      quantity: "",
      supplier_id: "",
      expiration: "",
      date_added: new Date().toISOString().split('T')[0], // Auto-set current date
      batch: generateBatchRef(), // Auto-generate batch number
      order_number: "",
      prescription: 0,
      bulk: 0
    });
    
    useEffect(() => {
    let buffer = "";
    let timeout;
  
    const handleKeyDown = (e) => {
      if (!scannerActive) return;
  
      console.log("Key pressed:", e.key, "KeyCode:", e.keyCode, "Scanner active:", scannerActive);
  
      if (timeout) clearTimeout(timeout);
  
      // Accept Enter key to complete scan
      if (e.key === "Enter") {
        if (buffer.length > 0) {
          console.log("Barcode scanned:", buffer);
          handleScannerOperation("SCAN_COMPLETE", { barcode: buffer });
          buffer = "";
        }
      } else {
        // Accept all characters (not just numbers) for barcode scanning
        buffer += e.key;
        console.log("Buffer updated:", buffer);
        timeout = setTimeout(() => {
          console.log("Buffer cleared due to timeout");
          buffer = ""; // Clear buffer after inactivity
        }, 1000); // Increased timeout to 1 second
      }
    };
  
    document.addEventListener("keydown", handleKeyDown);
    return () => document.removeEventListener("keydown", handleKeyDown);
  }, [scannerActive]);
  
  
    const [filterOptions, setFilterOptions] = useState({
      category: "",
      supplier: "",
      status: "",
      prescription: false,
      bulk: false,
    })
  
    // Generate batch reference function
    function generateBatchRef() {
      const now = new Date()
      const yyyy = now.getFullYear()
      const mm = String(now.getMonth() + 1).padStart(2, "0")
      const dd = String(now.getDate()).padStart(2, "0")
      const hh = String(now.getHours()).padStart(2, "0")
      const mi = String(now.getMinutes()).padStart(2, "0")
      const ss = String(now.getSeconds()).padStart(2, "0")
  
      return `BR-${yyyy}${mm}${dd}-${hh}${mi}${ss}`
    }
  
    // Removed unused form state variables since we're using modals now
  
    const [stats, setStats] = useState({
      totalProducts: 0,
      totalSuppliers: 0,
      storageCapacity: 0,
      warehouseValue: 0,
      lowStockItems: 0,
      expiringSoon: 0,
    })
  
    // Supplier form data
    const [supplierFormData, setSupplierFormData] = useState({
      supplier_name: "",
      supplier_address: "",
      supplier_contact: "",
      supplier_email: "",
      order_level: "",
      primary_phone: "",
      primary_email: "",
      contact_person: "",
      contact_title: "",
      payment_terms: "",
      lead_time_days: "",
      credit_rating: "",
      notes: "",
    });
  
    // Product form data
    const [formData, setFormData] = useState({
      product_name: "",
      barcode: "",
      category: "",
      description: "",
      variation: "",
      prescription: 0,
      bulk: 0,
      expiration: "",
      quantity: 0,
      unit_price: 0,
      supplier_id: "",
      location_id: "",
      brand: "",
    });
  
  
    // Edit form data
    const [editFormData, setEditFormData] = useState({})
    const [editProductFormData, setEditProductFormData] = useState({})
  
  
    // FIXED API Functions with better error handling
    async function handleCrudOperation(operation, data) {
      switch (operation) {
        case "DELETE_PRODUCT":
          setLoading(true);
          try {
            const response = await handleApiCall("delete_product", {
              product_id: data.product_id,
            });
            if (response.success) {
              toast.success("Product archived successfully");
              setShowDeleteModal(false);
              setSelectedItem(null);
              loadData("products");
            } else {
              toast.error(response.message || "Failed to delete product");
            }
          } catch (error) {
            console.error("Error deleting product:", error);
            toast.error("Failed to delete product");
          } finally {
            setLoading(false);
          }
          break;
    
        case "CREATE_SUPPLIER":
          setLoading(true);
          if (
            !data.supplier_name ||
            !data.supplier_contact ||
            !data.supplier_email
          ) {
            toast.error("Supplier name, contact, and email are required");
            setLoading(false);
            return;
          }
    
          try {
            const response = await handleApiCall("add_supplier", data);
            if (response.success) {
              toast.success(response.message || "Supplier added successfully");
              setShowSupplierModal(false);
              clearSupplierForm();
              loadData("suppliers");
            } else {
              toast.error(response.message || "Failed to add supplier");
            }
          } catch (error) {
            toast.error(
              "Failed to add supplier: " +
                (error?.response?.data?.message || error.message)
            );
            console.error("Error adding supplier:", error);
          } finally {
            setLoading(false);
          }
          break;
    
        case "UPDATE_SUPPLIER":
          setLoading(true);
          const updateData = {
            ...data,
            supplier_id: selectedItem?.supplier_id,
          };
          try {
            const response = await handleApiCall("update_supplier", updateData);
            if (response.success) {
              toast.success("Supplier updated successfully");
              setShowEditModal(false);
              setSelectedItem(null);
              clearEditForm();
              loadData("suppliers");
            } else {
              toast.error(response.message || "Failed to update supplier");
            }
          } catch (error) {
            console.error("Error updating supplier:", error);
            toast.error("Failed to update supplier");
          } finally {
            setLoading(false);
          }
          break;

        case "UPDATE_PRODUCT":
          setLoading(true);
          const updateProductData = {
            ...data,
            product_id: selectedItem?.product_id,
          };
          try {
            const response = await handleApiCall("update_product", updateProductData);
            if (response.success) {
              toast.success("Product updated successfully");
              setShowEditProductModal(false);
              setSelectedItem(null);
              setEditProductFormData({});
              loadData("products");
            } else {
              toast.error(response.message || "Failed to update product");
            }
          } catch (error) {
            console.error("Error updating product:", error);
            toast.error("Failed to update product");
          } finally {
            setLoading(false);
          }
          break;
    
        case "DELETE_SUPPLIER":
          setLoading(true);
          try {
            const response = await handleApiCall("delete_supplier", {
              supplier_id: data.supplier_id,
            });
            if (response.success) {
              toast.success("Supplier archived successfully");
              setShowDeleteModal(false);
              setSelectedItem(null);
              loadData("suppliers");
            } else {
              toast.error(response.message || "Failed to delete supplier");
            }
          } catch (error) {
            console.error("Error deleting supplier:", error);
            toast.error("Failed to delete supplier");
          } finally {
            setLoading(false);
          }
          break;
    
        case "CREATE_PRODUCT":
          // This case is now handled in the modal handlers
          console.log("CREATE_PRODUCT case is deprecated - use modal handlers instead");
          break;
    
        default:
          console.error("Unknown CRUD operation:", operation);
          toast.error("Unknown operation: " + operation);
      }
    }
    
  
    // FIXED Data Loading Functions
    function loadData(dataType) {
      switch (dataType) {
        case "suppliers":
          handleApiCall("get_suppliers")
            .then((response) => {
              console.log("Suppliers response:", response.data)
              let suppliersArray = []
  
              if (response.success && Array.isArray(response.data)) {
                suppliersArray = response.data
              } else if (Array.isArray(response.data)) {
                suppliersArray = response.data
              }
  
              setSuppliersData(suppliersArray)
              updateStats("totalSuppliers", suppliersArray.length)
              console.log("Suppliers loaded:", suppliersArray.length)
            })
            .catch((error) => {
              console.error("Error loading suppliers:", error)
              toast.error("Failed to load suppliers")
              setSuppliersData([])
            })
          break
            case "products":
                console.log("🔄 Loading warehouse products...");
                // After fixing database, uncomment the line below to only show warehouse products
                // handleApiCall("get_products", { location_id: 1 }) // Only load warehouse products (location_id = 1)
                handleApiCall("get_products") // Load all products for now
              .then((response) => {
                console.log("📦 Products API response:", response);
                console.log("📦 Products response.data:", response.data);
                let productsArray = [];
  
                if (Array.isArray(response.data)) {
                  productsArray = response.data;
                  console.log("✅ Products loaded from response.data array:", productsArray);
                } else if (response.data && Array.isArray(response.data.data)) {
                  productsArray = response.data.data;
                  console.log("✅ Products loaded from response.data.data array:", productsArray);
                } else {
                  console.warn("⚠️ Unexpected products response format:", response);
                }
  
                console.log("🔍 Final productsArray before filtering:", productsArray);
                console.log("🔍 productsArray.length:", productsArray.length);
                console.log("🔍 productsArray content:", JSON.stringify(productsArray, null, 2));
  
                  const activeProducts = productsArray.filter(
                    (product) => (product.status || "").toLowerCase() !== "archived"
                  );
  
                  // Remove products that are now in Convenience store (location_id === 4)
                  const warehouseProducts = activeProducts.filter(
                    (product) => product.location_id !== 4
                  );

                  console.log("🔍 Active products after filtering:", warehouseProducts);
                  console.log("🔍 Active products length:", warehouseProducts.length);

                  setInventoryData(warehouseProducts);
                  updateStats("totalProducts", warehouseProducts.length);
                  calculateWarehouseValue(warehouseProducts);
                  calculateLowStockAndExpiring(warehouseProducts); // <-- Add this line
                  console.log("✅ Products loaded successfully:", warehouseProducts.length, "products");
                })
                .catch((error) => {
                  console.error("❌ Error loading products:", error);
                  toast.error("Failed to load products");
                  setInventoryData([]);
                });
              break;
  
  
  
        case "batches":
          handleApiCall("get_batches")
            .then((response) => {
              console.log("Batches response:", response.data)
              let batchesArray = []
  
              if (Array.isArray(response.data)) {
                batchesArray = response.data
              } else if (response.data && Array.isArray(response.data.data)) {
                batchesArray = response.data.data
              }
  
              setBatchData(batchesArray)
              console.log("Batches loaded:", batchesArray.length)
            })
            .catch((error) => {
              console.error("Error loading batches:", error)
              toast.error("Failed to load batches")
              setBatchData([])
            })
          break
  
        case "brands":
          // Load brands from your database
          handleApiCall("get_brands")
            .then((response) => {
              console.log("Brands response:", response.data)
              let brandsArray = []
  
              if (Array.isArray(response.data)) {
                brandsArray = response.data
              } else if (response.data && Array.isArray(response.data.data)) {
                brandsArray = response.data.data
              }
  
              setBrandsData(brandsArray)
              console.log("Brands loaded:", brandsArray.length)
            })
            .catch((error) => {
              console.error("Error loading brands:", error)
              // Set default brands if API fails
              setBrandsData([
                { brand_id: 23, brand: "dawdawdaw" },
                { brand_id: 24, brand: "trust" },
                { brand_id: 25, brand: "rightmid" },
                { brand_id: 26, brand: "daw" },
                { brand_id: 27, brand: "dwa" },
                { brand_id: 28, brand: "dawd" },
              ])
            })
          break

        case "categories":
          // Load categories from your database
          console.log("🔄 Loading categories...");
          handleApiCall("get_categories")
            .then((response) => {
              console.log("📦 Categories API response:", response);
              console.log("📦 Categories response.data:", response.data);
              let categoriesArray = []
  
              if (Array.isArray(response.data)) {
                categoriesArray = response.data
                console.log("✅ Categories loaded from response.data array:", categoriesArray);
              } else if (response.data && Array.isArray(response.data.data)) {
                categoriesArray = response.data.data
                console.log("✅ Categories loaded from response.data.data array:", categoriesArray);
              } else {
                console.warn("⚠️ Unexpected categories response format:", response);
              }
  
              console.log("🔍 Final categoriesArray before setting:", categoriesArray);
              console.log("🔍 categoriesArray.length:", categoriesArray.length);
              console.log("🔍 categoriesArray content:", JSON.stringify(categoriesArray, null, 2));
              
              setCategoriesData(categoriesArray)
              console.log("✅ Categories loaded successfully:", categoriesArray.length, "categories");
              console.log("📋 Categories data:", categoriesArray);
            })
            .catch((error) => {
              console.error("❌ Error loading categories:", error)
              toast.error("Failed to load categories from database")
            })
          break
  
        case "all":
          loadData("suppliers")
          loadData("products")
          loadData("batches")
          loadData("brands")
          loadData("categories")
          break
  
        default:
          console.error("Unknown data type:", dataType)
      }
    }
  

    
    function updateStats(statName, value) {
      setStats((prev) => ({
        ...prev,
        [statName]: value,
      }))
    }
  
    function calculateWarehouseValue(products) {
      const totalValue = products.reduce((sum, product) => {
        return sum + (Number.parseFloat(product.quantity) || 0) * (Number.parseFloat(product.unit_price) || 0)
      }, 0)
      // Assume max capacity is 1000 products for demonstration
      const maxCapacity = 1000;
      const usedCapacity = products.reduce((sum, product) => sum + (Number(product.quantity) || 0), 0);
      const storageCapacity = Math.min(100, Math.round((usedCapacity / maxCapacity) * 100));
      setStats((prev) => ({
        ...prev,
        warehouseValue: totalValue,
        storageCapacity: storageCapacity,
      }))
    }
  
    // Reset Functions
    function clearSupplierForm() {
      setSupplierFormData({
        supplier_name: "",
        supplier_address: "",
        supplier_contact: "",
        supplier_email: "",
        order_level: "",
        primary_phone: "",
        primary_email: "",
        contact_person: "",
        contact_title: "",
        payment_terms: "",
        lead_time_days: "",
        credit_rating: "",
        notes: "",
      })
    }
  
    function clearEditForm() {
      setEditFormData({})
    }
  
    // Form Handlers
    function handleSupplierInputChange(field, value) {
      setSupplierFormData((prev) => ({
        ...prev,
        [field]: value,
      }))
    }
  
    function handleEditInputChange(field, value) {
      setEditFormData((prev) => ({
        ...prev,
        [field]: value,
      }))
    }

    function handleEditProductInputChange(field, value) {
      setEditProductFormData((prev) => ({
        ...prev,
        [field]: value,
      }))
    }
  
    // Enhanced Scanner Functions with Barcode Checking
  async function handleScannerOperation(operation, data) {
    console.log("Scanner operation:", operation, "Data:", data);
    
    switch (operation) {
      case "START_SCANNER":
        console.log("Starting scanner...");
        setScannerActive(true);
        setScannedBarcode("");
        setScannerStatusMessage("🔍 Scanning started... Please scan the product using your barcode scanner.");
  
        // Optional: timeout warning
        const timeoutId = setTimeout(() => {
          console.log("Scanner timeout - no barcode detected");
          setScannerStatusMessage("⚠️ No barcode detected. Please try again or check if your scanner is connected.");
          setScannerActive(false);
        }, 10000);
        setScanTimeout(timeoutId);
        break;
  
      case "SCAN_COMPLETE":
        console.log("Scan complete with barcode:", data.barcode);
        setScannerActive(false);
        if (scanTimeout) clearTimeout(scanTimeout);
  
        const scanned = data.barcode;
        setScannedBarcode(scanned);
        setScannerStatusMessage("✅ Barcode received! Checking if product exists...");
  
        try {
          console.log("Checking barcode in database:", scanned);
          // Check if barcode exists in database
          const barcodeCheck = await checkBarcodeExists(scanned);
          console.log("Barcode check result:", barcodeCheck);
          
          if (barcodeCheck.success && barcodeCheck.product) {
            console.log("Product found, opening update stock modal");
            // Product exists - show update stock modal
            setExistingProduct(barcodeCheck.product);
            setNewStockQuantity("");
            setShowUpdateStockModal(true);
            setScannerStatusMessage("✅ Product found! Opening update stock modal.");
          } else {
            console.log("Product not found, opening new product modal");
            // Product doesn't exist - show new product modal
            setNewProductForm({
              product_name: "",
              category: "",
              barcode: scanned, // Pre-fill with scanned barcode
              description: "",
              unit_price: "",
              brand_id: "",
              brand_search: "",
              quantity: "",
              supplier_id: "",
              expiration: "",
              batch: generateBatchRef(), // Auto-generate batch
              order_number: "",
              prescription: 0,
              bulk: 0
            });
            setShowNewProductModal(true);
            setScannerStatusMessage("✅ New product detected! Opening new product modal.");
          }
        } catch (error) {
          console.error("Error checking barcode:", error);
          setScannerStatusMessage("❌ Error checking barcode. Please try again.");
          toast.error("Failed to check barcode");
        }
        break;
  
      case "STOP_SCANNER":
        console.log("Stopping scanner...");
        setScannerActive(false);
        if (scanTimeout) clearTimeout(scanTimeout);
        setScannerStatusMessage("");
        break;
  
      default:
        console.error("Unknown scanner operation:", operation);
    }
  }
  
  
  
    // Event Handlers
    function handleAddSupplier(e) {
      e.preventDefault()
      console.log("Form submitted with data:", supplierFormData)
      handleCrudOperation("CREATE_SUPPLIER", supplierFormData)
    }
  
    function handleUpdateSupplier(e) {
      e.preventDefault()
      handleCrudOperation("UPDATE_SUPPLIER", editFormData)
    }

    function handleUpdateProduct(e) {
      e.preventDefault()
      handleCrudOperation("UPDATE_PRODUCT", editProductFormData)
    }
  
   function handleDeleteItem() {
    if (activeTab === "products") {
      handleCrudOperation("DELETE_PRODUCT", selectedItem);
    } else {
      handleCrudOperation("DELETE_SUPPLIER", selectedItem);
    }
  }
  
  
  
    // Removed handleSaveEntry since we're using modals now
  
    // Modal Actions
    function openSupplierModal() {
      clearSupplierForm()
      setShowSupplierModal(true)
    }
  
    function closeSupplierModal() {
      setShowSupplierModal(false)
      clearSupplierForm()
    }
  
    function openEditModal(item) {
      setSelectedItem(item)
      setEditFormData(item)
      setShowEditModal(true)
    }

    function openEditProductModal(product) {
      setSelectedItem(product)
      setEditProductFormData(product)
      setShowEditProductModal(true)
    }
  
    function closeEditModal() {
      setShowEditModal(false)
      setSelectedItem(null)
      clearEditForm()
    }

    function closeEditProductModal() {
      setShowEditProductModal(false)
      setSelectedItem(null)
      setEditProductFormData({})
    }
  
    function openDeleteModal(item) {
      setSelectedItem(item)
      setShowDeleteModal(true)
    }
  
    function closeDeleteModal() {
      setShowDeleteModal(false)
      setSelectedItem(null)
    }
  
    // New modal handlers for barcode scanning
    function closeUpdateStockModal() {
      setShowUpdateStockModal(false);
      setExistingProduct(null);
      setNewStockQuantity("");
    }

    function closeNewProductModal() {
      setShowNewProductModal(false);
      setNewProductForm({
        product_name: "",
        category: "",
        barcode: "",
        description: "",
        unit_price: "",
        brand_id: "",
        brand_search: "",
        quantity: "",
        supplier_id: "",
        expiration: "",
        date_added: new Date().toISOString().split('T')[0], // Auto-set current date
        batch: generateBatchRef(), // Auto-generate new batch when modal closes
        order_number: "",
        prescription: 0,
        bulk: 0
      });
    }

    // Form handlers for new product modal
    function handleNewProductInputChange(field, value) {
      setNewProductForm(prev => ({
        ...prev,
        [field]: value
      }));
    }

    // FIFO Functions
    async function getFifoStock(productId) {
      try {
        console.log("Calling get_fifo_stock API with product_id:", productId);
        const response = await handleApiCall("get_fifo_stock", { product_id: productId });
        console.log("get_fifo_stock API response:", response);
        return response;
      } catch (error) {
        console.error("Error getting FIFO stock:", error);
        return { success: false, error: error.message };
      }
    }

    async function getExpiringProducts(daysThreshold = 30) {
      try {
        const response = await handleApiCall("get_expiring_products", { days_threshold: daysThreshold });
        return response;
      } catch (error) {
        console.error("Error getting expiring products:", error);
        return { success: false, error: error.message };
      }
    }

    async function consumeStockFifo(productId, quantity, referenceNo = "", notes = "") {
      try {
        const response = await handleApiCall("consume_stock_fifo", { 
          product_id: productId, 
          quantity: quantity,
          reference_no: referenceNo,
          notes: notes,
          created_by: "admin"
        });
        return response;
      } catch (error) {
        console.error("Error consuming stock:", error);
        return { success: false, error: error.message };
      }
    }

    function openFifoModal(product) {
      setSelectedProductForFifo(product);
      setShowFifoModal(true);
      loadFifoStock(product.product_id);
    }

    function closeFifoModal() {
      setShowFifoModal(false);
      setSelectedProductForFifo(null);
      setFifoStockData([]);
    }



    async function loadFifoStock(productId) {
      console.log("Loading FIFO stock for product ID:", productId);
      const response = await getFifoStock(productId);
      console.log("FIFO stock response:", response);
      if (response.success) {
        setFifoStockData(response.data);
      } else {
        console.error("FIFO stock error:", response.message);
        toast.error("Failed to load FIFO stock data: " + (response.message || "Unknown error"));
      }
    }

    // Handle update stock submission
    async function handleUpdateStock() {
      if (!existingProduct || !newStockQuantity || newStockQuantity <= 0) {
        toast.error("Please enter a valid quantity");
        return;
      }

      setLoading(true);
      try {
        // Generate batch reference for new stock
        const batchRef = generateBatchRef();
        
        const response = await updateProductStock(
          existingProduct.product_id, 
          parseInt(newStockQuantity),
          batchRef,
          existingProduct.expiration,
          existingProduct.unit_price
        );
        
        if (response.success) {
          toast.success("Stock updated successfully with FIFO tracking");
          closeUpdateStockModal();
          loadData("products"); // Reload products to show updated stock
        } else {
          toast.error(response.message || "Failed to update stock");
        }
      } catch (error) {
        console.error("Error updating stock:", error);
        toast.error("Failed to update stock");
      } finally {
        setLoading(false);
      }
    }

    // Handle new product submission
    async function handleAddNewProduct(e) {
      e.preventDefault();
      
      if (!newProductForm.product_name || !newProductForm.category || !newProductForm.unit_price || !newProductForm.quantity) {
        toast.error("Please fill in all required fields");
        return;
      }

      setLoading(true);
      try {
        const productData = {
          product_name: newProductForm.product_name,
          category: newProductForm.category,
          barcode: newProductForm.barcode,
          description: newProductForm.description,
          unit_price: parseFloat(newProductForm.unit_price),
          brand_id: newProductForm.brand_id || 30, // Default brand
          quantity: parseInt(newProductForm.quantity),
          supplier_id: newProductForm.supplier_id || 13, // Default supplier
          expiration: newProductForm.expiration || null,
          date_added: newProductForm.date_added, // Auto-set date
          prescription: newProductForm.prescription,
          bulk: newProductForm.bulk,
          location: "Warehouse",
          status: "active",
          stock_status: "in stock",
          reference: newProductForm.batch || generateBatchRef(), // Batch number
          order_no: newProductForm.order_number || "" // Order number
        };

        const response = await handleApiCall("add_product", productData);
        if (response.success) {
          toast.success("Product added successfully");
          closeNewProductModal();
          loadData("products"); // Reload products
        } else {
          toast.error(response.message || "Failed to add product");
        }
      } catch (error) {
        console.error("Error adding product:", error);
        toast.error("Failed to add product");
      } finally {
        setLoading(false);
      }
    }


  
    // Component Lifecycle
    useEffect(() => {
      // Fetch warehouse KPIs on mount
      async function fetchWarehouseKPIs() {
        try {
          const response = await handleApiCall("get_warehouse_kpis", { location: "warehouse" });
          if (response && response.success !== false && response !== null) {
            setStats((prev) => ({
              ...prev,
              totalProducts: response.totalProducts ?? prev.totalProducts,
              totalSuppliers: response.totalSuppliers ?? prev.totalSuppliers,
              storageCapacity: response.storageCapacity ?? prev.storageCapacity,
              warehouseValue: response.warehouseValue ?? prev.warehouseValue,
              lowStockItems: response.lowStockItems ?? prev.lowStockItems,
              expiringSoon: response.expiringSoon ?? prev.expiringSoon,
            }));
          }
        } catch (error) {
          console.error("Failed to fetch warehouse KPIs", error);
        }
      }
      fetchWarehouseKPIs();
      loadData("all");
    }, [])
  
    // Debug useEffect to track categoriesData changes
    useEffect(() => {
      console.log("🔄 categoriesData changed:", categoriesData);
      console.log("🔄 categoriesData length:", categoriesData.length);
      if (categoriesData.length > 0) {
        console.log("🔄 First category:", categoriesData[0]);
        console.log("🔄 All categories:", categoriesData.map(cat => cat.category_name));
      }
    }, [categoriesData])

    function calculateLowStockAndExpiring(products) {
      // Low stock threshold can also be made dynamic if needed
      const LOW_STOCK_THRESHOLD = 10;

      // Get expiry warning days from localStorage, fallback to 30
      const expiryWarningDays = parseInt(localStorage.getItem("expiryWarningDays")) || 30;

      const now = new Date();

      // Low stock: quantity <= threshold
      const lowStockCount = products.filter(
        (product) => Number(product.quantity) <= LOW_STOCK_THRESHOLD
      ).length;

      // Expiring soon: expiration within threshold days
      const expiringSoonCount = products.filter((product) => {
        if (!product.expiration) return false;
        const expDate = new Date(product.expiration);
        const diffDays = (expDate - now) / (1000 * 60 * 60 * 24);
        return diffDays >= 0 && diffDays <= expiryWarningDays;
      }).length;

      setStats((prev) => ({
        ...prev,
        lowStockItems: lowStockCount,
        expiringSoon: expiringSoonCount,
      }));
    }

  

    return (
      <div className="min-h-screen bg-white p-6">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Enguio's Pharmacy [Stock Master]</h1>
          <p className="text-gray-600">Manage your inventory, suppliers, and stock levels</p>
        </div>
  


        {/* Enhanced Status Bar - KEPT SCANNER FUNCTIONALITY */}
        <div className="bg-gray-50 rounded-lg border border-gray-300 mb-6">
          <div className="p-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-6">
                <div className="flex items-center space-x-2">
                  <MapPin className="h-4 w-4 text-blue-600" />
                  <span className="text-sm font-medium">Current Location:</span>
                  <span className="inline-block px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                    {currentLocation.toUpperCase()}
                  </span>
                </div>
                <div className="flex items-center space-x-2">
                  <Scan className="h-4 w-4 text-green-600" />
                  <span className="text-sm font-medium">Scanner:</span>
                  <span
                    className={`inline-block px-2 py-0.5 text-xs font-medium rounded-full ${
                      scannerActive ? "bg-green-100 text-green-800" : "bg-gray-200 text-gray-600"
                    }`}
                  >
                    {scannerActive ? "SCANNING..." : "READY"}
                  </span>
                </div>
              </div>
              <div className="flex items-center space-x-4">
                <button
                  onClick={() => handleScannerOperation("START_SCANNER")}
                  disabled={scannerActive}
                  className="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded flex items-center disabled:opacity-50"
                >
                  <Camera className="h-4 w-4 mr-2" />
                  {scannerActive ? "Scanning..." : "Start Scanner"}
                </button>
                <button
                  onClick={openSupplierModal}
                  className="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded flex items-center"
                >
                  <Plus className="h-4 w-4 mr-2" />
                  Add Supplier
                </button>

              </div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-6 gap-6 mb-8">
          {[
            { title: "Total Products", value: stats.totalProducts, icon: Package, color: "blue" },
            { title: "Total Suppliers", value: stats.totalSuppliers, icon: User, color: "green" },
            { title: "Storage Capacity", value: `${stats.storageCapacity}%`, icon: Truck, color: "yellow" },
            {
              title: "Warehouse Value",
              value: `₱${stats.warehouseValue.toLocaleString()}`,
              icon: DollarSign,
              color: "purple",
            },
            { title: "Low Stock Items", value: stats.lowStockItems, icon: Package, color: "red" },
            { title: "Expiring Soon", value: stats.expiringSoon, icon: Package, color: "orange" },
          ].map((stat, index) => (
            <div key={index} className="bg-gray-50 rounded-lg border border-gray-300 p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-700">{stat.title}</p>
                  <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
                </div>
                <stat.icon className={`h-8 w-8 text-${stat.color}-600`} />
              </div>
            </div>
          ))}
        </div>
   
        {/* Search and Filter Bar */}
        <div className="bg-gray-50 rounded-lg border border-gray-300 mb-6 p-4">
          <div className="flex items-center justify-between space-x-4">
            <div className="flex items-center space-x-4 flex-1">
              <div className="relative flex-1 max-w-md">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <input
                  type="text"
                  placeholder={`Search ${activeTab}...`}
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>
          </div>
        </div>
  
        {/* Tabs for Products and Suppliers */}
        <div className="bg-gray-50 rounded-lg border border-gray-300 mb-6">
          <div className="border-b border-gray-300">
            <nav className="-mb-px flex">
              <button
                onClick={() => setActiveTab("products")}
                className={`py-2 px-4 border-b-2 font-medium text-sm ${
                  activeTab === "products"
                    ? "border-blue-500 text-blue-600"
                    : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                }`}
              >
                Products ({inventoryData.length})
              </button>
              <button
                onClick={() => setActiveTab("suppliers")}
                className={`py-2 px-4 border-b-2 font-medium text-sm ${
                  activeTab === "suppliers"
                    ? "border-blue-500 text-blue-600"
                    : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                }`}
              >
                Suppliers ({suppliersData.length})
              </button>
            </nav>
          </div>
  
  <div className="p-6">
    {activeTab === "products" && (
      <div className="overflow-x-auto">
        {console.log("🔍 Rendering products table with inventoryData:", inventoryData)}
        {console.log("🔍 inventoryData.length:", inventoryData.length)}
        <table className="w-full border-collapse border border-gray-300">
          <thead>
            <tr className="bg-blue-100">
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Product Name</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Barcode</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Category</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Brand</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Quantity</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Unit Price</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Supplier</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Batch</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Expiration</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Date Added</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Type</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Status</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Stock Level</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Actions</th>
            </tr>
          </thead>
          <tbody>
            {inventoryData.length === 0 ? (
              <tr>
                <td colSpan="14" className="border border-gray-300 px-3 py-2 text-center text-gray-500">
                  No products found. {console.log("🔍 No products to display")}
                </td>
              </tr>
            ) : (
              inventoryData.map((product) => {
                console.log("🔍 Rendering product:", product);
                return (
                  <tr key={product.product_id} className="hover:bg-gray-50">
                    <td className="border border-gray-300 px-3 py-2 font-medium">{product.product_name}</td>
                    <td className="border border-gray-300 px-3 py-2 font-mono text-sm">{product.barcode}</td>
                    <td className="border border-gray-300 px-3 py-2">{product.category}</td>
                    <td className="border border-gray-300 px-3 py-2">{product.brand || "N/A"}</td>
                    <td className="border border-gray-300 px-3 py-2">{product.quantity}</td>
                    <td className="border border-gray-300 px-3 py-2">₱{Number.parseFloat(product.unit_price || 0).toFixed(2)}</td>
                    <td className="border border-gray-300 px-3 py-2">{product.supplier_name || "N/A"}</td>
      
                    {/* Batch */}
                    <td className="border border-gray-300 px-3 py-2 text-sm text-center">
                      {product.batch_reference || <span className="text-gray-400 italic">None</span>}
                    </td>
      
                    {/* Expiration */}
                    <td className="border border-gray-300 px-3 py-2 text-sm text-center">
                      {product.expiration ? new Date(product.expiration).toLocaleDateString() : <span className="text-gray-400 italic">None</span>}
                    </td>

                    {/* Date Added */}
                    <td className="border border-gray-300 px-3 py-2 text-sm text-center">
                      {product.date_added ? new Date(product.date_added).toLocaleDateString('en-US', {
                        month: '2-digit',
                        day: '2-digit',
                        year: '2-digit'
                      }) : <span className="text-gray-400 italic">N/A</span>}
                    </td>
      
                    {/* Type - Bulk / Rx / Both / None */}
                    <td className="border border-gray-300 px-3 py-2 text-center">
                      {(() => {
                        const bulk = Number(product.bulk);
                        const prescription = Number(product.prescription);
      
                        if (bulk && prescription) {
                          return <span className="inline-block px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Bulk & Rx</span>;
                        } else if (bulk) {
                          return <span className="inline-block px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Bulk</span>;
                        } else if (prescription) {
                          return <span className="inline-block px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 rounded-full">Rx</span>;
                        } else {
                          return <span className="inline-block px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Regular</span>;
                        }
                      })()}
                    </td>
      
                    {/* Status (Active or Archived) */}
                    <td className="border border-gray-300 px-3 py-2 text-center">
                      <span className={`inline-block px-2 py-0.5 text-xs font-medium rounded-full ${
                        product.status === "Available"
                          ? "bg-green-100 text-green-800"
                          : "bg-red-100 text-red-800"
                      }`}>
                        {product.status || "Available"}
                      </span>
                    </td>
      
                    {/* Stock Level */}
                    <td className="border border-gray-300 px-3 py-2 text-center">
                      <span className={`inline-block px-2 py-0.5 text-xs font-medium rounded-full ${
                        product.stock_status === 'out of stock'
                          ? 'bg-red-100 text-red-700'
                          : product.stock_status === 'low stock'
                          ? 'bg-yellow-100 text-yellow-700'
                          : 'bg-green-100 text-green-700'
                      }`}>
                        {product.stock_status}
                      </span>
                    </td>
      
                    {/* Actions */}
                    <td className="border border-gray-300 px-3 py-2 text-center">
                      <div className="flex items-center justify-center space-x-2">
                        <button onClick={() => openFifoModal(product)} className="text-green-500 hover:text-green-700" title="View FIFO Stock">
                          <Package className="h-4 w-4" />
                        </button>
                        <button onClick={() => openEditProductModal(product)} className="text-blue-500 hover:text-blue-700" title="Edit Product">
                          <Edit className="h-4 w-4" />
                        </button>
                        <button onClick={() => openDeleteModal(product)} className="text-orange-500 hover:text-orange-700" title="Archive Product">
                          <Archive className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>
    )}
  
            {activeTab === "suppliers" && (
              <div className="overflow-x-auto">
                <table className="w-full border-collapse border border-gray-300">
                  <thead>
                    <tr className="bg-blue-100">
                      <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                        Supplier Name
                      </th>
                      <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                        Contact
                      </th>
                      <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                        Email
                      </th>
                      <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                        Contact Person
                      </th>
                      <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                        Payment Terms
                      </th>
                      <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                        Lead Time
                      </th>
                      <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                        Actions
                      </th>
                      
                    </tr>
                  </thead>
                  <tbody>
                    {suppliersData.map((supplier) => (
                      <tr key={supplier.supplier_id} className="hover:bg-gray-50">
                        <td className="border border-gray-300 px-3 py-2 font-medium">{supplier.supplier_name}</td>
                        <td className="border border-gray-300 px-3 py-2">{supplier.supplier_contact}</td>
                        <td className="border border-gray-300 px-3 py-2">{supplier.supplier_email}</td>
                        <td className="border border-gray-300 px-3 py-2">{supplier.contact_person || "-"}</td>
                        <td className="border border-gray-300 px-3 py-2">{supplier.payment_terms || "-"}</td>
                        <td className="border border-gray-300 px-3 py-2">
                          {supplier.lead_time_days ? `${supplier.lead_time_days} days` : "-"}
                        </td>
                        <td className="border border-gray-300 px-3 py-2 text-center">
                          <div className="flex items-center justify-center space-x-2">
                            <button onClick={() => openEditModal(supplier)} className="text-blue-500 hover:text-blue-700">
                              <Edit className="h-4 w-4" />
                            </button>
                            <button onClick={() => openDeleteModal(supplier)} className="text-orange-500 hover:text-orange-700" title="Archive Supplier">
                              <Archive className="h-4 w-4" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
  
        {/* SUPPLIER MODAL - ALL FIELDS KEPT */}
        {showSupplierModal && (
          <div className="fixed inset-0 bg-gray-100 bg-opacity-80 flex items-center justify-center z-50">
            <div className="rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto border-2 border-gray-400">
              <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 className="text-lg font-semibold text-gray-900">Add New Supplier</h3>
                <button onClick={closeSupplierModal} className="text-gray-400 hover:text-gray-600">
                  <X className="h-6 w-6" />
                </button>
              </div>
  
              <form onSubmit={handleAddSupplier} className="p-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Supplier Name *</label>
                    <input
                      type="text"
                      required
                      value={supplierFormData.supplier_name}
                      onChange={(e) => handleSupplierInputChange("supplier_name", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Contact Number *</label>
                    <input
                      type="text"
                      required
                      value={supplierFormData.supplier_contact}
                      onChange={(e) => handleSupplierInputChange("supplier_contact", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input
                      type="email"
                      required
                      value={supplierFormData.supplier_email}
                      onChange={(e) => handleSupplierInputChange("supplier_email", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Primary Phone</label>
                    <input
                      type="text"
                      value={supplierFormData.primary_phone}
                      onChange={(e) => handleSupplierInputChange("primary_phone", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Primary Email</label>
                    <input
                      type="email"
                      value={supplierFormData.primary_email}
                      onChange={(e) => handleSupplierInputChange("primary_email", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                    <input
                      type="text"
                      value={supplierFormData.contact_person}
                      onChange={(e) => handleSupplierInputChange("contact_person", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Contact Title</label>
                                        <input
                      type="text"
                      value={supplierFormData.contact_title}
                      onChange={(e) => handleSupplierInputChange("contact_title", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Payment Terms</label>
                    <input
                      type="text"
                      value={supplierFormData.payment_terms}
                      onChange={(e) => handleSupplierInputChange("payment_terms", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Lead Time (Days)</label>
                    <input
                      type="number"
                      value={supplierFormData.lead_time_days}
                      onChange={(e) => handleSupplierInputChange("lead_time_days", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Order Level</label>
                    <input
                      type="number"
                      value={supplierFormData.order_level}
                      onChange={(e) => handleSupplierInputChange("order_level", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Credit Rating</label>
                    <input
                      type="text"
                      value={supplierFormData.credit_rating}
                      onChange={(e) => handleSupplierInputChange("credit_rating", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                                    <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea
                      rows={3}
                      value={supplierFormData.supplier_address}
                      onChange={(e) => handleSupplierInputChange("supplier_address", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea
                      rows={3}
                      value={supplierFormData.notes}
                      onChange={(e) => handleSupplierInputChange("notes", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                </div>
  
                <div className="flex justify-end space-x-4 mt-6">
                  <button
                    type="button"
                    onClick={closeSupplierModal}
                    className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={loading}
                    className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md disabled:opacity-50"
                  >
                    {loading ? "Adding..." : "Add Supplier"}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
  
        {/* Edit Supplier Modal */}
        {showEditModal && (
          <div className="fixed inset-0 bg-gray-100 bg-opacity-80 flex items-center justify-center z-50">
            <div className="bg-transparent backdrop-blur-sm rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto border-2 border-gray-400">
              <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 className="text-lg font-semibold text-gray-900">Edit Supplier</h3>
                <button onClick={closeEditModal} className="text-gray-400 hover:text-gray-600">
                  <X className="h-6 w-6" />
                </button>
              </div>
  
              <form onSubmit={handleUpdateSupplier} className="p-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Supplier Name *</label>
                    <input
                      type="text"
                      required
                      value={editFormData.supplier_name || ""}
                      onChange={(e) => handleEditInputChange("supplier_name", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Contact Number *</label>
                                        <input
                      type="text"
                      required
                      value={editFormData.supplier_contact || ""}
                      onChange={(e) => handleEditInputChange("supplier_contact", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input
                      type="email"
                      required
                      value={editFormData.supplier_email || ""}
                      onChange={(e) => handleEditInputChange("supplier_email", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                    <input
                      type="text"
                      value={editFormData.contact_person || ""}
                      onChange={(e) => handleEditInputChange("contact_person", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Payment Terms</label>
                    <input
                      type="text"
                      value={editFormData.payment_terms || ""}
                      onChange={(e) => handleEditInputChange("payment_terms", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Lead Time (Days)</label>
                    <input
                      type="number"
                      value={editFormData.lead_time_days || ""}
                      onChange={(e) => handleEditInputChange("lead_time_days", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                                    <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea
                      rows={3}
                      value={editFormData.supplier_address || ""}
                      onChange={(e) => handleEditInputChange("supplier_address", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea
                      rows={3}
                      value={editFormData.notes || ""}
                      onChange={(e) => handleEditInputChange("notes", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                </div>
  
                <div className="flex justify-end space-x-4 mt-6">
                  <button
                    type="button"
                    onClick={closeEditModal}
                    className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={loading}
                    className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md disabled:opacity-50"
                  >
                    {loading ? "Updating..." : "Update Supplier"}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* Edit Product Modal */}
        {showEditProductModal && (
          <div className="fixed inset-0 bg-gray-100 bg-opacity-80 flex items-center justify-center z-50">
            <div className="bg-transparent backdrop-blur-sm rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto border-2 border-gray-400">
              <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 className="text-lg font-semibold text-gray-900">Edit Product</h3>
                <button onClick={closeEditProductModal} className="text-gray-400 hover:text-gray-600">
                  <X className="h-6 w-6" />
                </button>
              </div>
  
              <form onSubmit={handleUpdateProduct} className="p-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                    <input
                      type="text"
                      required
                      value={editProductFormData.product_name || ""}
                      onChange={(e) => handleEditProductInputChange("product_name", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Barcode</label>
                    <input
                      type="text"
                      value={editProductFormData.barcode || ""}
                      onChange={(e) => handleEditProductInputChange("barcode", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                    <select
                      required
                      value={editProductFormData.category || ""}
                      onChange={(e) => handleEditProductInputChange("category", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">Select Category</option>
                      {categoriesData.map((category) => (
                        <option key={category.category_id} value={category.category_name}>
                          {category.category_name}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Unit Price *</label>
                    <input
                      type="number"
                      step="0.01"
                      required
                      value={editProductFormData.unit_price || ""}
                      onChange={(e) => handleEditProductInputChange("unit_price", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                    <input
                      type="number"
                      required
                      value={editProductFormData.quantity || ""}
                      onChange={(e) => handleEditProductInputChange("quantity", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <select
                      value={editProductFormData.brand_id || ""}
                      onChange={(e) => handleEditProductInputChange("brand_id", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">Select Brand</option>
                      {brandsData.map((brand) => (
                        <option key={brand.brand_id} value={brand.brand_id}>
                          {brand.brand}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                    <select
                      value={editProductFormData.supplier_id || ""}
                      onChange={(e) => handleEditProductInputChange("supplier_id", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">Select Supplier</option>
                      {suppliersData.map((supplier) => (
                        <option key={supplier.supplier_id} value={supplier.supplier_id}>
                          {supplier.supplier_name}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Expiration Date</label>
                    <input
                      type="date"
                      value={editProductFormData.expiration || ""}
                      onChange={(e) => handleEditProductInputChange("expiration", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea
                      rows={3}
                      value={editProductFormData.description || ""}
                      onChange={(e) => handleEditProductInputChange("description", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div className="md:col-span-2">
                    <div className="flex items-center space-x-6">
                      <div className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          id="editPrescription"
                          checked={editProductFormData.prescription === 1}
                          onChange={(e) => handleEditProductInputChange("prescription", e.target.checked ? 1 : 0)}
                          className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label htmlFor="editPrescription" className="text-sm font-medium text-gray-700">
                          Prescription Required
                        </label>
                      </div>
                      <div className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          id="editBulk"
                          checked={editProductFormData.bulk === 1}
                          onChange={(e) => handleEditProductInputChange("bulk", e.target.checked ? 1 : 0)}
                          className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label htmlFor="editBulk" className="text-sm font-medium text-gray-700">
                          Bulk Product
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
  
                <div className="flex justify-end space-x-4 mt-6">
                  <button
                    type="button"
                    onClick={closeEditProductModal}
                    className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={loading}
                    className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md disabled:opacity-50"
                  >
                    {loading ? "Updating..." : "Update Product"}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
  
  
  {/* Delete Confirmation Modal */}
  {showDeleteModal && (
    <div className="fixed inset-0 bg-gray-100 bg-opacity-80 flex items-center justify-center z-50">
      <div className="bg-transparent backdrop-blur-sm rounded-lg shadow-xl p-6 border-2 border-gray-400 w-96">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Confirm Archive</h3>
        <p className="text-gray-700 mb-4">Are you sure you want to archive this item?</p>
        <div className="flex justify-end space-x-4">
          <button
            type="button"
            onClick={closeDeleteModal}
            className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Cancel
          </button>
                      <button
              type="button"
              onClick={handleDeleteItem}
              className="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-md disabled:opacity-50"
            >
              {loading ? "Archiving..." : "Archive"}
            </button>
        </div>
      </div>
    </div>
  )}
  
  {/* Update Product Stock Modal */}
  {showUpdateStockModal && existingProduct && (
    <div className="fixed inset-0 bg-gray-100 bg-opacity-80 flex items-center justify-center z-50">
      <div className="bg-transparent backdrop-blur-sm rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto border-2 border-gray-400">
        <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h3 className="text-lg font-semibold text-gray-900">Update Product Stock</h3>
          <button onClick={closeUpdateStockModal} className="text-gray-400 hover:text-gray-600">
            <X className="h-6 w-6" />
          </button>
        </div>

        <div className="p-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
              <input
                type="text"
                value={existingProduct.product_name}
                readOnly
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent text-gray-700"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Barcode</label>
              <input
                type="text"
                value={existingProduct.barcode}
                readOnly
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent text-gray-700"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
              <input
                type="text"
                value={existingProduct.category}
                readOnly
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent text-gray-700"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Brand</label>
              <input
                type="text"
                value={existingProduct.brand || "N/A"}
                readOnly
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent text-gray-700"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Current Stock</label>
              <input
                type="text"
                value={existingProduct.quantity}
                readOnly
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent text-gray-700"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Unit Price</label>
              <input
                type="text"
                value={`₱${Number.parseFloat(existingProduct.unit_price || 0).toFixed(2)}`}
                readOnly
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent text-gray-700"
              />
            </div>
            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea
                value={existingProduct.description || ""}
                readOnly
                rows={3}
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent text-gray-700"
              />
            </div>
          </div>

          <div className="border-t pt-6">
            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-1">New Stock to Add *</label>
              <input
                type="number"
                value={newStockQuantity}
                onChange={(e) => setNewStockQuantity(e.target.value)}
                placeholder="Enter quantity to add"
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div className="text-sm text-gray-600 mb-4">
              Current Stock: <span className="font-semibold">{existingProduct.quantity}</span> | 
              New Total: <span className="font-semibold">{existingProduct.quantity + (parseInt(newStockQuantity) || 0)}</span>
            </div>
          </div>

          <div className="flex justify-end space-x-4 mt-6">
            <button
              type="button"
              onClick={closeUpdateStockModal}
              className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="button"
              onClick={handleUpdateStock}
              disabled={loading || !newStockQuantity || newStockQuantity <= 0}
              className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md disabled:opacity-50"
            >
              {loading ? "Updating..." : "Update Stock"}
            </button>
          </div>
        </div>
      </div>
    </div>
  )}

            {showNewProductModal && (
        <div className="fixed inset-0 bg-gray-100 bg-opacity-80 flex items-center justify-center z-50">
          <div className="bg-transparent backdrop-blur-sm rounded-lg shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto border-2 border-gray-400">
        <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h3 className="text-lg font-semibold text-gray-900">Add New Product</h3>
          <button onClick={closeNewProductModal} className="text-gray-400 hover:text-gray-600">
            <X className="h-6 w-6" />
          </button>
        </div>

        <form onSubmit={handleAddNewProduct} className="p-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
              <input
                type="text"
                required
                value={newProductForm.product_name}
                onChange={(e) => handleNewProductInputChange("product_name", e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Barcode</label>
              <input
                type="text"
                value={newProductForm.barcode}
                readOnly
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent text-gray-700"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Category *</label>
              <select
                required
                value={newProductForm.category}
                onChange={(e) => {
                  handleNewProductInputChange("category", e.target.value);
                }}
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Select Category</option>
                {categoriesData.map((category) => (
                  <option key={category.category_id} value={category.category_name}>
                    {category.category_name}
                  </option>
                ))}
              </select>
              <p className="text-xs text-gray-500 mt-1">Available categories: {categoriesData.length}</p>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Unit Price *</label>
              <input
                type="number"
                step="0.01"
                required
                value={newProductForm.unit_price}
                onChange={(e) => handleNewProductInputChange("unit_price", e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Initial Stock *</label>
              <input
                type="number"
                required
                value={newProductForm.quantity}
                onChange={(e) => handleNewProductInputChange("quantity", e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Brand</label>
              <div className="relative">
                <input
                  type="text"
                  placeholder="Type to search brands..."
                  value={newProductForm.brand_search || ""}
                  onChange={(e) => {
                    const searchTerm = e.target.value;
                    handleNewProductInputChange("brand_search", searchTerm);
                    // Find the brand that matches the search term
                    const matchingBrand = brandsData.find(brand => 
                      brand.brand.toLowerCase().startsWith(searchTerm.toLowerCase())
                    );
                    if (matchingBrand) {
                      handleNewProductInputChange("brand_id", matchingBrand.brand_id);
                    } else {
                      handleNewProductInputChange("brand_id", "");
                    }
                  }}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                {newProductForm.brand_search && (
                  <div className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-40 overflow-y-auto">
                    {brandsData
                      .filter(brand => 
                        brand.brand.toLowerCase().startsWith(newProductForm.brand_search.toLowerCase())
                      )
                      .map((brand) => (
                        <div
                          key={brand.brand_id}
                          className="px-3 py-2 hover:bg-gray-100 cursor-pointer"
                          onClick={() => {
                            handleNewProductInputChange("brand_search", brand.brand);
                            handleNewProductInputChange("brand_id", brand.brand_id);
                          }}
                        >
                          {brand.brand}
                        </div>
                      ))}
                  </div>
                )}
              </div>
              <p className="text-xs text-gray-500 mt-1">Available brands: {brandsData.length}</p>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
              <select
                value={newProductForm.supplier_id}
                onChange={(e) => handleNewProductInputChange("supplier_id", e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Select Supplier</option>
                {suppliersData.map((supplier) => (
                  <option key={supplier.supplier_id} value={supplier.supplier_id}>
                    {supplier.supplier_name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Expiration Date</label>
              <input
                type="date"
                value={newProductForm.expiration}
                onChange={(e) => handleNewProductInputChange("expiration", e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Batch Number</label>
              <input
                type="text"
                value={newProductForm.batch}
                onChange={(e) => handleNewProductInputChange("batch", e.target.value)}
                placeholder="Enter batch number"
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <button
                type="button"
                onClick={() => handleNewProductInputChange("batch", generateBatchRef())}
                className="mt-1 px-3 py-1 text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 rounded border border-blue-300"
              >
                Generate New Batch
              </button>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Order Number</label>
              <input
                type="text"
                value={newProductForm.order_number}
                onChange={(e) => handleNewProductInputChange("order_number", e.target.value)}
                placeholder="Enter order number"
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Date Added</label>
              <input
                type="date"
                value={newProductForm.date_added}
                readOnly
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent text-gray-700 cursor-not-allowed"
              />
              <p className="text-xs text-gray-500 mt-1">Automatically set to current date</p>
            </div>
            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea
                rows={3}
                value={newProductForm.description}
                onChange={(e) => handleNewProductInputChange("description", e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div className="md:col-span-2">
              <div className="flex items-center space-x-6">
                <div className="flex items-center space-x-2">
                  <input
                    type="checkbox"
                    id="newPrescription"
                    checked={newProductForm.prescription === 1}
                    onChange={(e) => handleNewProductInputChange("prescription", e.target.checked ? 1 : 0)}
                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label htmlFor="newPrescription" className="text-sm font-medium text-gray-700">
                    Prescription Required
                  </label>
                </div>
                <div className="flex items-center space-x-2">
                  <input
                    type="checkbox"
                    id="newBulk"
                    checked={newProductForm.bulk === 1}
                    onChange={(e) => handleNewProductInputChange("bulk", e.target.checked ? 1 : 0)}
                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label htmlFor="newBulk" className="text-sm font-medium text-gray-700">
                    Bulk Product
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div className="flex justify-end space-x-4 mt-6">
            <button
              type="button"
              onClick={closeNewProductModal}
              className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={loading}
              className="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md disabled:opacity-50"
            >
              {loading ? "Adding..." : "Add Product"}
            </button>
          </div>
        </form>
      </div>
    </div>
  )}
  
  
  
        {/* FIFO Stock Modal */}
        {showFifoModal && selectedProductForFifo && (
          <div className="fixed inset-0 bg-gray-100 bg-opacity-80 flex items-center justify-center z-50">
            <div className="bg-transparent backdrop-blur-sm rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto border-2 border-gray-400">
              <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 className="text-lg font-semibold text-gray-900">
                  FIFO Stock Details - {selectedProductForFifo.product_name}
                </h3>
                <button onClick={closeFifoModal} className="text-gray-400 hover:text-gray-600">
                  <X className="h-6 w-6" />
                </button>
              </div>

              <div className="p-6">
                <div className="mb-6">
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div className="bg-blue-50 p-4 rounded-lg">
                      <h4 className="font-semibold text-blue-900">Product Info</h4>
                      <p className="text-sm text-blue-700">Barcode: {selectedProductForFifo.barcode}</p>
                      <p className="text-sm text-blue-700">Category: {selectedProductForFifo.category}</p>
                      <p className="text-sm text-blue-700">Total Stock: {selectedProductForFifo.quantity}</p>
                    </div>
                    <div className="bg-green-50 p-4 rounded-lg">
                      <h4 className="font-semibold text-green-900">Stock Status</h4>
                      <p className="text-sm text-green-700">Status: {selectedProductForFifo.stock_status}</p>
                      <p className="text-sm text-green-700">Unit Price: ₱{Number.parseFloat(selectedProductForFifo.unit_price || 0).toFixed(2)}</p>
                    </div>
                    <div className="bg-yellow-50 p-4 rounded-lg">
                      <h4 className="font-semibold text-yellow-900">FIFO Summary</h4>
                      <p className="text-sm text-yellow-700">Batches: {fifoStockData.length}</p>
                      <p className="text-sm text-yellow-700">Available: {fifoStockData.reduce((sum, batch) => sum + parseInt(batch.available_quantity), 0)}</p>
                    </div>
                  </div>
                </div>

                <div className="overflow-x-auto">
                  <table className="w-full border-collapse border border-gray-300">
                    <thead>
                      <tr className="bg-gray-100">
                        <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold">FIFO Order</th>
                        <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold">Batch Reference</th>
                        <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold">Available Qty</th>
                        <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold">Unit Cost</th>
                        <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold">Expiration Date</th>
                        <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold">Days Until Expiry</th>
                        <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold">Batch Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      {fifoStockData.map((batch, index) => (
                        <tr key={batch.summary_id} className="hover:bg-gray-50">
                          <td className="border border-gray-300 px-3 py-2 text-center font-medium">
                            #{index + 1}
                          </td>
                          <td className="border border-gray-300 px-3 py-2 font-mono text-sm">
                            {batch.batch_reference}
                          </td>
                          <td className="border border-gray-300 px-3 py-2 text-center">
                            <span className={`inline-block px-2 py-1 text-xs font-medium rounded-full ${
                              batch.available_quantity <= 0 ? 'bg-red-100 text-red-700' :
                              batch.available_quantity <= 10 ? 'bg-yellow-100 text-yellow-700' :
                              'bg-green-100 text-green-700'
                            }`}>
                              {batch.available_quantity}
                            </span>
                          </td>
                          <td className="border border-gray-300 px-3 py-2">
                            ₱{Number.parseFloat(batch.unit_cost || 0).toFixed(2)}
                          </td>
                          <td className="border border-gray-300 px-3 py-2 text-center">
                            {batch.expiration_date ? new Date(batch.expiration_date).toLocaleDateString() : 'N/A'}
                          </td>
                          <td className="border border-gray-300 px-3 py-2 text-center">
                            {batch.days_until_expiry !== null ? (
                              <span className={`inline-block px-2 py-1 text-xs font-medium rounded-full ${
                                batch.days_until_expiry <= 7 ? 'bg-red-100 text-red-700' :
                                batch.days_until_expiry <= 30 ? 'bg-yellow-100 text-yellow-700' :
                                'bg-green-100 text-green-700'
                              }`}>
                                {batch.days_until_expiry} days
                              </span>
                            ) : 'N/A'}
                          </td>
                          <td className="border border-gray-300 px-3 py-2 text-center text-sm">
                            {batch.batch_date ? new Date(batch.batch_date).toLocaleDateString() : 'N/A'}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                {fifoStockData.length === 0 && (
                  <div className="text-center py-8">
                    <Package className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                    <p className="text-gray-500">No FIFO stock data available for this product.</p>
                    <p className="text-sm text-gray-400 mt-2">This product may not have batch tracking enabled.</p>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}



        <ToastContainer />
      </div>
    )
  }

  export default Warehouse;