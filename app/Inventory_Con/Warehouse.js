
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
  Trash2,
} from "lucide-react";


function Warehouse() {
    // State Management
    const [scannerStatusMessage, setScannerStatusMessage] = useState("");
    const [scanTimeout, setScanTimeout] = useState(null);
  
    const [inventoryData, setInventoryData] = useState([])
    const [suppliersData, setSuppliersData] = useState([])
    const [batchData, setBatchData] = useState([])
    const [brandsData, setBrandsData] = useState([])
    const [searchTerm, setSearchTerm] = useState("")
    const [loading, setLoading] = useState(false)
    const [showAddModal, setShowAddModal] = useState(false)
    const [showSupplierModal, setShowSupplierModal] = useState(false)
    const [showEditModal, setShowEditModal] = useState(false)
    const [showDeleteModal, setShowDeleteModal] = useState(false)
    const [activeTab, setActiveTab] = useState("products")
    const [currentLocation, setCurrentLocation] = useState("warehouse")
    const [scannerActive, setScannerActive] = useState(false)
    const [scannedBarcode, setScannedBarcode] = useState("")
    const [selectedItem, setSelectedItem] = useState(null)
    const [useSameBatch, setUseSameBatch] = useState(true)
    const [showProductModal, setShowProductModal] = useState(false);
    const [selectedProducts, setSelectedProducts] = useState([]);
    
    useEffect(() => {
    let buffer = "";
    let timeout;
  
    const handleKeyDown = (e) => {
      if (!scannerActive) return;
  
      if (timeout) clearTimeout(timeout);
  
      // Only accept numbers and Enter key
      if (e.key === "Enter") {
        if (buffer.length > 0) {
          handleScannerOperation("SCAN_COMPLETE", { barcode: buffer });
          buffer = "";
        }
      } else {
        buffer += e.key;
        timeout = setTimeout(() => {
          buffer = ""; // Clear buffer after inactivity
        }, 500);
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
  
    // Form header data
    const [headerData, setHeaderData] = useState({
      supplier_id: "",
      location: "Warehouse",
      order_no: "",
      bill_to: "",
      reference: generateBatchRef(),
      order_ref: "",
      entry_by: "admin",
      expiration: "",
      ship_from: "",
      entry_date: new Date().toISOString().split("T")[0],
      entry_time: new Date().toLocaleTimeString(),
    })
  
    // Form options
    const [formOptions, setFormOptions] = useState({
      bulk: false,
      prescriptionAttachment: false,
    })
  
    // Line items for the table - UPDATED with brand field
    const [lineItems, setLineItems] = useState([
      {
        id: 1,
        title: "",
        sku: "",
        s_code: "",
        category: "",
        brand: "",
        units: "Pcs",
        unit_qty: "",
        c_stock: "",
        rate: "",
        disc: "",
        status: "In Stocks",
        description: "",
        variation: "",
        l_total: "",
      },
    ])
  
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
  
    // API Configuration - UPDATED to match your backend
    const API_BASE_URL = "http://localhost/capstone_api/backend.php"
  
    // FIXED API Functions with better error handling
    async function handleApiCall(action, data = {}) {
    const payload = { action, ...data };
    console.log("🚀 API Call Payload:", payload);
  
    try {
      const response = await axios.post(API_BASE_URL, payload, {
        headers: {
          "Content-Type": "application/json",
        },
        timeout: 10000,
      });
  
      const resData = response.data;
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
  
      if (error.response) {
        console.error("❌ Server responded with error:", error.response.data);
        return {
          success: false,
          message:
            error.response.data?.message || `Server error: ${error.response.status}`,
          error: error.response.data,
        };
      } else if (error.request) {
        console.error("❌ No response received:", error.request);
        return {
          success: false,
          message: "No response from server. Is it running?",
          error: "NO_RESPONSE",
        };
      } else {
        console.error("❌ Axios setup error:", error.message);
        return {
          success: false,
          message: error.message,
          error: "REQUEST_SETUP_ERROR",
        };
      }
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
                handleApiCall("get_products")
              .then((response) => {
                let productsArray = [];
  
                if (Array.isArray(response.data)) {
                  productsArray = response.data;
                } else if (response.data && Array.isArray(response.data.data)) {
                  productsArray = response.data.data;
                }
  
                  const activeProducts = productsArray.filter(
                    (product) => (product.status || "").toLowerCase() !== "archived"
                  );
  
                  setInventoryData(activeProducts);
                  updateStats("totalProducts", activeProducts.length);
                })
                .catch((error) => {
                  console.error("Error loading products:", error);
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
  
        case "all":
          loadData("suppliers")
          loadData("products")
          loadData("batches")
          loadData("brands")
          break
  
        default:
          console.error("Unknown data type:", dataType)
      }
    }
  
    // FIXED CRUD Operations with better error handling
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
          const validItems = lineItems.filter((item) => {
            return (
              (item.title && item.title.trim() !== "") ||
              (item.sku && item.sku.trim() !== "")
            );
          });
    
          if (validItems.length === 0) {
            toast.error("Please add at least one product with a name or barcode");
            return;
          }
    
          if (!headerData.supplier_id) {
            toast.error("Please select a supplier");
            return;
          }
    
          setLoading(true);
          try {
            const results = [];
            for (let i = 0; i < validItems.length; i++) {
              const item = validItems[i];
    
              let brand_id = 1;
              const selectedBrand = brandsData.find(
                (brand) =>
                  brand.brand.trim().toLowerCase() ===
                  (item.brand || "").trim().toLowerCase()
              );
    
              if (selectedBrand?.brand_id) {
                brand_id = parseInt(selectedBrand.brand_id);
              }
    
              const productData = {
                product_name: item.title?.trim() || `Product ${i + 1}`,
                category: item.category?.trim() || "General",
                barcode: item.sku?.trim() || `AUTO-${Date.now()}-${i}`,
                description: `${item.title?.trim() || "Product"} - ${item.s_code?.trim() || "No Code"}`,
                variation: item.variation?.trim() || "",
                prescription: formOptions.prescriptionAttachment ? 1 : 0,
                bulk: formOptions.bulk ? 1 : 0,
                quantity: Number.parseInt(item.unit_qty) || 0,
                unit_price: Number.parseFloat(item.rate) || 0,
                supplier_id: parseInt(headerData.supplier_id),
                location: headerData.location, // Send location name, backend will convert to ID
                reference: headerData.reference, // Send batch reference, backend will create/find batch
                brand_id,
                expiration: headerData.expiration || null,
                entry_by: headerData.entry_by || "admin",
                order_no: headerData.order_no || "",
                status: "active",
                stock_status: "in stock"
              };
              
    
              try {
                const result = await handleApiCall("add_product", productData);
                results.push(result);
    
                if (!result.success) {
             
                  console.log("📤 Sent product data:", productData);
                  console.log("📥 Server response:", result);
  
                }
              } catch (error) {
                console.error(`❌ Product ${i + 1} error:`, error.message);
                results.push({ success: false, message: error.message });
              }
            }
    
            const successCount = results.filter((r) => r.success).length;
            const failedCount = results.length - successCount;
    
            if (successCount > 0) {
              toast.success(
                `Saved ${successCount} product${
                  successCount > 1 ? "s" : ""
                }${failedCount > 0 ? `, ${failedCount} failed` : ""}`
              );
              loadData("products");
              loadData("batches");
              clearLineItems();
              setHeaderData((prev) => ({
                ...prev,
                reference: generateBatchRef(),
              }));
            } else {
              toast.error("No products were saved. Check console.");
            }
          } catch (error) {
            console.error("Error in CREATE_PRODUCT:", error);
            toast.error("Failed to save products: " + error.message);
          } finally {
            setLoading(false);
          }
          break;
    
        default:
          console.error("Unknown CRUD operation:", operation);
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
      updateStats("warehouseValue", totalValue)
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
  
    function clearLineItems() {
      setLineItems([
        {
          id: 1,
          title: "",
          sku: "",
          s_code: "",
          category: "",
          brand: "",
          units: "Pcs",
          unit_qty: "",
          c_stock: "",
          rate: "",
          disc: "",
          status: "In Stocks",
          l_total: "",
          description: "",
          variation: "",
        },
      ])
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
  
    function handleHeaderChange(field, value) {
      setHeaderData((prev) => ({
        ...prev,
        [field]: value,
      }))
    }
  
    function handleOptionChange(option, checked) {
      setFormOptions((prev) => ({
        ...prev,
        [option]: checked,
      }))
    }
  
    function updateLineItem(id, field, value) {
      setLineItems((prev) => prev.map((item) => (item.id === id ? { ...item, [field]: value } : item)))
    }
  
    function addLineItem() {
      const newId = Math.max(...lineItems.map((item) => item.id)) + 1
  
      setLineItems((prev) => [
        ...prev,
        {
          id: newId,
          title: "",
          sku: "",
          s_code: "",
          category: "",
          brand: "",
          units: "Pcs",
          unit_qty: "",
          c_stock: "",
          rate: "",
          disc: "",
          status: "In Stocks",
          l_total: "",
          description: "",
          variation: "",
        },
      ])
    }
  
    function removeLineItem(id) {
      if (lineItems.length > 1) {
        setLineItems((prev) => prev.filter((item) => item.id !== id))
      }
    }
  
    function calculateTotal(item) {
      const qty = Number.parseFloat(item.unit_qty) || 0
      const rate = Number.parseFloat(item.rate) || 0
      const disc = Number.parseFloat(item.disc) || 0
  
      const subtotal = qty * rate
      const afterDiscount = subtotal - (subtotal * disc) / 100
  
      return afterDiscount.toFixed(2)
    }
  
    // Scanner Functions - KEPT INTACT
  function handleScannerOperation(operation, data) {
    switch (operation) {
      case "START_SCANNER":
        setScannerActive(true);
        setScannedBarcode("");
        setScannerStatusMessage("🔍 Scanning started... Please scan the product using your barcode scanner.");
  
        // Optional: timeout warning
        const timeoutId = setTimeout(() => {
          setScannerStatusMessage("⚠️ No barcode detected. Please try again or check if your scanner is connected.");
          setScannerActive(false);
        }, 10000);
        setScanTimeout(timeoutId);
        break;
  
      case "SCAN_COMPLETE":
        setScannerActive(false);
        if (scanTimeout) clearTimeout(scanTimeout);
  
        const scanned = data.barcode;
        setScannedBarcode(scanned);
        setScannerStatusMessage("✅ Barcode received! The scanned value has been automatically entered into the Barcode No. field.");
  
        const firstEmptyItem = lineItems.find((item) => !item.sku);
        if (firstEmptyItem) {
          updateLineItem(firstEmptyItem.id, "sku", scanned);
        }
  
        toast.success(`Barcode scanned: ${scanned}`);
        break;
  
      case "STOP_SCANNER":
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
  
   function handleDeleteItem() {
    if (activeTab === "products") {
      handleCrudOperation("DELETE_PRODUCT", selectedItem);
    } else {
      handleCrudOperation("DELETE_SUPPLIER", selectedItem);
    }
  }
  
  
  
    function handleSaveEntry() {
      handleCrudOperation("CREATE_PRODUCT")
    }
  
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
  
    function closeEditModal() {
      setShowEditModal(false)
      setSelectedItem(null)
      clearEditForm()
    }
  
    function openDeleteModal(item) {
      setSelectedItem(item)
      setShowDeleteModal(true)
    }
  
    function closeDeleteModal() {
      setShowDeleteModal(false)
      setSelectedItem(null)
    }
  
    // Component Lifecycle
    useEffect(() => {
      console.log("Component mounted, loading data...")
      loadData("all")
    }, [])
  
    // Auto-calculate line item totals
    useEffect(() => {
      setLineItems((prev) =>
        prev.map((item) => ({
          ...item,
          l_total: calculateTotal(item),
        })),
      )
    }, [lineItems.map((item) => `${item.unit_qty}-${item.rate}-${item.disc}`).join(",")])
  
    // Render Functions
    function renderLineItemRow(item, index) {
      return (
        <tr key={item.id} className="hover:bg-gray-50">
          <td className="border border-gray-300 px-2 py-1">
            <input
              type="text"
              value={item.title || ""}
              onChange={(e) => updateLineItem(item.id, "title", e.target.value)}
              placeholder={`Product ${index + 1}`}
              className="w-full border-0 p-1 h-8 text-sm focus:outline-none"
            />
          </td>
          <td className="border border-gray-300 px-2 py-1">
            <input
              type="text"
              value={item.sku || ""}
              onChange={(e) => updateLineItem(item.id, "sku", e.target.value)}
              placeholder={`ITE10000${index + 1}`}
              className="w-full border-0 p-1 h-8 text-sm font-mono focus:outline-none"
            />
          </td>
          <td className="border border-gray-300 px-2 py-1">
            <input
              type="text"
              value={item.category || ""}
              onChange={(e) => updateLineItem(item.id, "category", e.target.value)}
              placeholder="Category"
              className="w-full border-0 p-1 h-8 text-sm focus:outline-none"
            />
          </td>
          <td className="border border-gray-300 px-2 py-1">
            <input
              type="text"
              value={item.s_code || ""}
              onChange={(e) => updateLineItem(item.id, "s_code", e.target.value)}
              placeholder="SUP-1001"
              className="w-full border-0 p-1 h-8 text-sm focus:outline-none"
            />
          </td>
          <td className="border border-gray-300 px-2 py-1">
            <input
              type="text"
              value={item.brand || ""}
              onChange={(e) => updateLineItem(item.id, "brand", e.target.value)}
              placeholder="Enter Brand"
              className="w-full border-0 p-1 h-8 text-sm focus:outline-none"
            />
          </td>
  
          <td className="border border-gray-300 px-2 py-1">
            <select
              value={item.units}
              onChange={(e) => updateLineItem(item.id, "units", e.target.value)}
              className="w-full border-0 p-1 h-8 text-sm focus:outline-none"
            >
              <option value="Pcs">Pcs</option>
              <option value="Kg">Kg</option>
              <option value="Box">Box</option>
              <option value="Bundle">Bundle</option>
            </select>
          </td>
          <td className="border border-gray-300 px-2 py-1">
            <input
              type="number"
              value={item.unit_qty || ""}
              onChange={(e) => updateLineItem(item.id, "unit_qty", e.target.value)}
              placeholder="1"
              className="w-full border-0 p-1 h-8 text-sm focus:outline-none"
            />
          </td>
          <td className="border border-gray-300 px-2 py-1">
            <input
              type="text"
              value={item.c_stock || ""}
              onChange={(e) => updateLineItem(item.id, "c_stock", e.target.value)}
              placeholder="500"
              className="w-full border-0 p-1 h-8 text-sm focus:outline-none"
            />
          </td>
          <td className="border border-gray-300 px-2 py-1">
            <input
              type="number"
              step="0.01"
              value={item.rate || ""}
              onChange={(e) => updateLineItem(item.id, "rate", e.target.value)}
              placeholder="500.00"
              className="w-full border-0 p-1 h-8 text-sm focus:outline-none"
            />
          </td>
          <td className="border border-gray-300 px-2 py-1">
            <input
              type="number"
              step="0.01"
              value={item.disc || ""}
              onChange={(e) => updateLineItem(item.id, "disc", e.target.value)}
              placeholder="0"
              className="w-full border-0 p-1 h-8 text-sm focus:outline-none"
            />
          </td>
          <td className="border border-gray-300 px-2 py-1">
            <select
              value={item.status}
              onChange={(e) => updateLineItem(item.id, "status", e.target.value)}
              className="w-full border-0 p-1 h-8 text-sm focus:outline-none"
            >
              <option value="In Stocks">In Stocks</option>
              <option value="Out of Stock">Out of Stock</option>
              <option value="Low Stock">Low Stock</option>
            </select>
          </td>
              <td className="border border-gray-300 px-2 py-1">
                  <input
              type="text"
              value={item.description || ""}
              onChange={(e) => updateLineItem(item.id, "description", e.target.value)}
              placeholder="Description"
              className="w-full border-0 p-1 h-8 text-sm focus:outline-none"
            />
  
          </td>
          <td className="border border-gray-300 px-2 py-1">
                  <input
              type="text"
              value={item.variation || ""}
              onChange={(e) => updateLineItem(item.id, "variation", e.target.value)}
  
              placeholder="Variation  "
              className="w-full border-0 p-1 h-8 text-sm focus:outline-none"
            />
  
          </td>
          <td className="border border-gray-300 px-2 py-1">
            <input
              type="text"
              value={calculateTotal(item)}
              disabled
              className="w-full border-0 p-1 h-8 text-sm bg-gray-50 font-semibold focus:outline-none"
            />
          </td>
          <td className="border border-gray-300 px-2 py-1 text-center">
            <button
              type="button"
              onClick={() => removeLineItem(item.id)}
              className="h-6 w-6 p-0 text-red-600 hover:text-red-800 bg-transparent border-none cursor-pointer"
            >
              <X className="h-4 w-4" />
            </button>
          </td>
        </tr>
      )
    }
  
    // Main Render
    return (
      <div className="min-h-screen bg-gray-50 p-6">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Warehouse Management System</h1>
          <p className="text-gray-600">Manage your inventory, suppliers, and stock levels</p>
        </div>
  
        {/* Enhanced Status Bar - KEPT SCANNER FUNCTIONALITY */}
        <div className="bg-white rounded-lg shadow-md border border-gray-200 mb-6">
          <div className="p-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-6">
                <div className="flex items-center space-x-2">
                  <MapPin className="h-4 w-4 text-blue-600" />
                  <span className="text-sm font-medium">Current Location:</span>
                  <span className="inline-block px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                    {currentLocation.toUpperCase()}
                  </span>
                </div>
                <div className="flex items-center space-x-2">
                  <Scan className="h-4 w-4 text-purple-600" />
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
                  className="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded flex items-center disabled:opacity-50"
                >
                  <Camera className="h-4 w-4 mr-2" />
                  {scannerActive ? "Scanning..." : "Start Scanner"}
                </button>
                <button
                  onClick={openSupplierModal}
                  className="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded flex items-center"
                >
                  <Plus className="h-4 w-4 mr-2" />
                  Add Supplier
                </button>
              </div>
            </div>
          </div>
        </div>
  
        {/* Enhanced Stats Cards */}
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
            <div key={index} className="bg-white rounded-lg shadow-md border border-gray-200 p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">{stat.title}</p>
                  <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
                </div>
                <stat.icon className={`h-8 w-8 text-${stat.color}-600`} />
              </div>
            </div>
          ))}
        </div>
  
        {/* Main Form - KEPT ALL INPUT FIELDS */}
        <div className="bg-white rounded-lg shadow-md border border-gray-200 mb-6">
          <div className="px-6 py-4 border-b border-gray-200 flex items-center space-x-2">
            <Package className="h-5 w-5 text-blue-600" />
            <h2 className="text-xl font-bold text-gray-900">Product Entry Form</h2>
          </div>
  
          <div className="p-6">
            {/* Header Information - ALL FIELDS KEPT */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
              {/* Left Column */}
              <div className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <div className="text-sm font-medium text-gray-700 mb-1">Supplier Name</div>
                    <select
                      value={headerData.supplier_id}
                      onChange={(e) => handleHeaderChange("supplier_id", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">Select Supplier</option>
                      {suppliersData.map((supplier) => (
                        <option key={supplier.supplier_id} value={supplier.supplier_id}>
                          {supplier.supplier_id} - {supplier.supplier_name}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <div className="text-sm font-medium text-gray-700 mb-1">Location</div>
                    <input
                      type="text"
                      value={headerData.location}
                      onChange={(e) => handleHeaderChange("location", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                  </div>
                </div>
                <div>
                  <div className="text-sm font-medium text-gray-700 mb-1">Supplier Info</div>
                  <textarea
                    value={(() => {
                      if (!headerData || !suppliersData || suppliersData.length === 0) return ""
  
                      const selectedSupplier = suppliersData.find(
                        (s) => String(s.supplier_id) === String(headerData.supplier_id),
                      )
  
                      return selectedSupplier
                        ? `${selectedSupplier.supplier_name}, ${selectedSupplier.supplier_address}, ${selectedSupplier.supplier_contact}, ${selectedSupplier.supplier_email}`
                        : ""
                    })()}
                    readOnly
                    rows={3}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-700 focus:outline-none"
                  />
                </div>
              </div>
  
              {/* Middle Column */}
              <div className="space-y-4">
                <div>
                  <div className="text-sm font-medium text-gray-700 mb-1">Batch Reference</div>
                  <input
                    type="text"
                    value={headerData.reference}
                    disabled
                    className="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600"
                  />
                </div>
                <div>
                  <div className="text-sm font-medium text-gray-700 mb-1">Entry By</div>
                  <input
                    type="text"
                    value={headerData.entry_by}
                    onChange={(e) => handleHeaderChange("entry_by", e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
                <div>
                  <div className="text-sm font-medium text-gray-700 mb-1">Entry Date</div>
                  <input
                    type="date"
                    value={headerData.entry_date}
                    onChange={(e) => handleHeaderChange("entry_date", e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
                <div>
                  <div className="text-sm font-medium text-gray-700 mb-1">Entry Time</div>
                  <input
                    type="text"
                    value={headerData.entry_time}
                    onChange={(e) => handleHeaderChange("entry_time", e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
              </div>
  
              {/* Right Column */}
              <div className="space-y-4">
                <div>
                  <div className="text-sm font-medium text-gray-700 mb-1">Order No</div>
                  <input
                    type="text"
                    value={headerData.order_no}
                    onChange={(e) => handleHeaderChange("order_no", e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
                <div>
                  <div className="text-sm font-medium text-gray-700 mb-1">Order Ref</div>
                  <input
                    type="text"
                    value={headerData.order_ref}
                    onChange={(e) => handleHeaderChange("order_ref", e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
              <div>
              <div className="text-sm font-medium text-gray-700 mb-1">Expiration Date</div>
              <input
                type="date"
                value={headerData.expiration || ""}
                onChange={(e) => handleHeaderChange("expiration", e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
              </div>
            </div>
  
            {/* Options Checkboxes */}
            <div className="flex items-center space-x-6 mb-6 p-4 bg-gray-50 rounded-lg">
              <div className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  id="bulk"
                  checked={formOptions.bulk}
                  onChange={(e) => handleOptionChange("bulk", e.target.checked)}
                  className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label htmlFor="bulk" className="text-sm font-medium text-gray-700">
                  Bulk
                </label>
              </div>
              <div className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  id="prescriptionAttachment"
                  checked={formOptions.prescriptionAttachment}
                  onChange={(e) => handleOptionChange("prescriptionAttachment", e.target.checked)}
                  className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label htmlFor="prescriptionAttachment" className="text-sm font-medium text-gray-700">
                  Prescription Attachment
                </label>
              </div>
            </div>
  
            {/* Items Table - ALL COLUMNS KEPT */}
            <div className="overflow-x-auto">
              <table className="w-full border-collapse border border-gray-300">
                <thead>
                  <tr className="bg-blue-100">
                    <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                      Product Name
                    </th>
                    <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                      Barcode no.
                    </th>
                    <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                      Category
                    </th>
                    <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                      S.CODE
                    </th>
                    <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                      Brand
                    </th>
                    <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                      UNITS
                    </th>
                    <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                      UNIT-QTY
                    </th>
                    <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                      C.STOCK
                    </th>
                    <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                      RATE
                    </th>
                    <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                      DISC
                    </th>
                    <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                      Status
                    </th>
                      <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                      Description
                    </th>
                    <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                      Variation
                    </th>
                    <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">
                      L.TOTAL
                    </th>
                    <th className="border border-gray-300 px-3 py-2 text-center text-sm font-semibold text-blue-900">
                      X
                    </th>
                  </tr>
                </thead>
                <tbody>{lineItems.map(renderLineItemRow)}</tbody>
                </table>
                 </div>
  
              <div className="flex items-center space-x-2 mb-4 mt-4">
              <span className="text-sm text-gray-700">
                Current Batch Reference:
                <span className="ml-1 font-mono text-blue-600">{headerData.reference}</span>
              </span>
              <button
                onClick={() =>
                  setHeaderData((prev) => ({
                    ...prev,
                    reference: generateBatchRef(),
                  }))
                }
                className="text-sm text-blue-600 hover:underline"
              >
                Generate New Batch
              </button>
            </div>
  
            {/* Add Row Button */}
            <div className="mt-4 flex justify-start">
              <button
                type="button"
                onClick={addLineItem}
                className="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <Plus className="h-4 w-4" />
                <span>Add Row</span>
              </button>
            </div>
  
            {/* Form Actions */}
            <div className="flex justify-end space-x-4 mt-6 pt-6 border-t">
              <button
                type="button"
                onClick={clearLineItems}
                className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                Clear
              </button>
             
              <button
                type="button"
                onClick={handleSaveEntry}
                disabled={loading}
                className="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50"
              >
                {loading ? "Saving..." : "Save Entry"}
              </button>
            </div>
          </div>
        </div>
  
        {/* Search and Filter Bar */}
        <div className="bg-white rounded-lg shadow-md border border-gray-200 mb-6 p-4">
          <div className="flex items-center justify-between space-x-4">
            <div className="flex items-center space-x-4 flex-1">
              <div className="relative flex-1 max-w-md">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <input
                  type="text"
                  placeholder={`Search ${activeTab}...`}
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>
          </div>
        </div>
  
        {/* Tabs for Products and Suppliers */}
        <div className="bg-white rounded-lg shadow-md border border-gray-200 mb-6">
          <div className="border-b border-gray-200">
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
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Type</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Status</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Stock Level</th>
              <th className="border border-gray-300 px-3 py-2 text-left text-sm font-semibold text-blue-900">Actions</th>
            </tr>
          </thead>
          <tbody>
            {inventoryData.map((product) => (
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
                    <button onClick={() => openEditModal(product)} className="text-blue-500 hover:text-blue-700">
                      <Edit className="h-4 w-4" />
                    </button>
                    <button onClick={() => openDeleteModal(product)} className="text-red-500 hover:text-red-700">
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
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
                            <button onClick={() => openDeleteModal(supplier)} className="text-red-500 hover:text-red-700">
                              <Trash2 className="h-4 w-4" />
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
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
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
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Contact Number *</label>
                    <input
                      type="text"
                      required
                      value={supplierFormData.supplier_contact}
                      onChange={(e) => handleSupplierInputChange("supplier_contact", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input
                      type="email"
                      required
                      value={supplierFormData.supplier_email}
                      onChange={(e) => handleSupplierInputChange("supplier_email", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Primary Phone</label>
                    <input
                      type="text"
                      value={supplierFormData.primary_phone}
                      onChange={(e) => handleSupplierInputChange("primary_phone", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Primary Email</label>
                    <input
                      type="email"
                      value={supplierFormData.primary_email}
                      onChange={(e) => handleSupplierInputChange("primary_email", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                    <input
                      type="text"
                      value={supplierFormData.contact_person}
                      onChange={(e) => handleSupplierInputChange("contact_person", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Contact Title</label>
                    <input
                      type="text"
                      value={supplierFormData.contact_title}
                      onChange={(e) => handleSupplierInputChange("contact_title", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Payment Terms</label>
                    <input
                      type="text"
                      value={supplierFormData.payment_terms}
                      onChange={(e) => handleSupplierInputChange("payment_terms", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Lead Time (Days)</label>
                    <input
                      type="number"
                      value={supplierFormData.lead_time_days}
                      onChange={(e) => handleSupplierInputChange("lead_time_days", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Order Level</label>
                    <input
                      type="number"
                      value={supplierFormData.order_level}
                      onChange={(e) => handleSupplierInputChange("order_level", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Credit Rating</label>
                    <input
                      type="text"
                      value={supplierFormData.credit_rating}
                      onChange={(e) => handleSupplierInputChange("credit_rating", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea
                      rows={3}
                      value={supplierFormData.supplier_address}
                      onChange={(e) => handleSupplierInputChange("supplier_address", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea
                      rows={3}
                      value={supplierFormData.notes}
                      onChange={(e) => handleSupplierInputChange("notes", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
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
  
        {/* Edit Modal */}
        {showEditModal && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
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
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Contact Number *</label>
                    <input
                      type="text"
                      required
                      value={editFormData.supplier_contact || ""}
                      onChange={(e) => handleEditInputChange("supplier_contact", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input
                      type="email"
                      required
                      value={editFormData.supplier_email || ""}
                      onChange={(e) => handleEditInputChange("supplier_email", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                    <input
                      type="text"
                      value={editFormData.contact_person || ""}
                      onChange={(e) => handleEditInputChange("contact_person", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Payment Terms</label>
                    <input
                      type="text"
                      value={editFormData.payment_terms || ""}
                      onChange={(e) => handleEditInputChange("payment_terms", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Lead Time (Days)</label>
                    <input
                      type="number"
                      value={editFormData.lead_time_days || ""}
                      onChange={(e) => handleEditInputChange("lead_time_days", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea
                      rows={3}
                      value={editFormData.supplier_address || ""}
                      onChange={(e) => handleEditInputChange("supplier_address", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
  
                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea
                      rows={3}
                      value={editFormData.notes || ""}
                      onChange={(e) => handleEditInputChange("notes", e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
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
  
  
  {/* Delete Confirmation Modal */}
  {showDeleteModal && (
    <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-50">
      <div className="bg-white rounded-lg shadow-xl p-6 border border-gray-300 w-96">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Confirm archive</h3>
        <p className="text-gray-700 mb-4">Are you sure you want to archive  this item?</p>
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
            className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md disabled:opacity-50"
          >
            {loading ? "Deleting..." : "Delete"}
          </button>
        </div>
      </div>
    </div>
  )}
  
  
  
        <ToastContainer />
      </div>
    )
  }

  export default Warehouse;