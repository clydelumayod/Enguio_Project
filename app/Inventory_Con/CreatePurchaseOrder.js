"use client";

import React, { useState, useEffect } from "react";
import { toast } from "react-toastify";

// Define API base URLs at the top of the file
const API_BASE_SIMPLE = "http://localhost/Enguio_Project/purchase_order_api_simple.php";
const API_BASE = "http://localhost/Enguio_Project/purchase_order_api.php";

function CreatePurchaseOrder() {
  // Tab stateasy
  const [activeTab, setActiveTab] = useState('create');
  
  // Create Purchase Order states
  const [formData, setFormData] = useState({
    supplier: "",
    orderDate: new Date().toISOString().split('T')[0],
    expectedDelivery: "",
    notes: "",
  });
  const [selectedProducts, setSelectedProducts] = useState([]);
  const [suppliers, setSuppliers] = useState([]);
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(false);
  const [currentUser, setCurrentUser] = useState({ emp_id: 21 }); // Mock user ID

  // Purchase Order List states
  const [purchaseOrders, setPurchaseOrders] = useState([]);
  const [poLoading, setPoLoading] = useState(true);
  const [selectedPO, setSelectedPO] = useState(null);
  const [showDetails, setShowDetails] = useState(false);

  // Receive Items states
  const [receivingList, setReceivingList] = useState([]);
  const [receiveLoading, setReceiveLoading] = useState(true);
  const [showReceiveForm, setShowReceiveForm] = useState(false);
  const [receiveFormData, setReceiveFormData] = useState({
    delivery_receipt_no: "",
    notes: "",
    items: []
  });

  useEffect(() => {
    if (activeTab === 'create') {
      fetchSuppliers();
      fetchProducts();
    } else if (activeTab === 'list') {
      fetchPurchaseOrders();
    } else if (activeTab === 'receive') {
      fetchReceivingList();
    }
  }, [activeTab]);

  // Create Purchase Order functions
  const fetchSuppliers = async () => {
    try {
      const response = await fetch(`${API_BASE_SIMPLE}?action=suppliers`);
      const data = await response.json();
      if (data.success) {
        setSuppliers(data.data);
      } else {
        toast.error('Failed to load suppliers');
      }
    } catch (error) {
      console.error('Error fetching suppliers:', error);
      toast.error('Error loading suppliers');
    }
  };

  const fetchProducts = async () => {
    try {
      const response = await fetch(`${API_BASE_SIMPLE}?action=products`);
      const data = await response.json();
      if (data.success) {
        setProducts(data.data);
      } else {
        toast.error('Failed to load products');
      }
    } catch (error) {
      console.error('Error fetching products:', error);
      toast.error('Error loading products');
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const addProduct = () => {
    const newProduct = {
      id: Date.now(),
      productId: "",
      quantity: 1,
      unitPrice: 0,
      total: 0
    };
    setSelectedProducts([...selectedProducts, newProduct]);
  };

  const removeProduct = (index) => {
    setSelectedProducts(selectedProducts.filter((_, i) => i !== index));
  };

  const safeNumber = (val) => {
    const num = typeof val === 'string' && val.trim() === '' ? 0 : Number(val);
    return Number.isNaN(num) ? 0 : num;
  };

  const updateProduct = (index, field, value) => {
    const updatedProducts = [...selectedProducts];
    let safeValue = value;

    if (field === 'quantity' || field === 'unitPrice') {
      safeValue = safeNumber(value);
    }

    updatedProducts[index] = {
      ...updatedProducts[index],
      [field]: safeValue
    };

    // Calculate total if productId, quantity, or unitPrice changed
    if (field === 'productId' || field === 'quantity' || field === 'unitPrice') {
      const product = updatedProducts[index];
      const quantity = safeNumber(product.quantity);
      const unitPrice = safeNumber(product.unitPrice);
      if (product.productId && quantity && unitPrice) {
        product.total = quantity * unitPrice;
      } else {
        product.total = 0;
      }
    }

    // Auto-fill unit price when product is selected
    if (field === 'productId' && value) {
      const selectedProduct = products.find(p => p.product_id == value);
      if (selectedProduct) {
        updatedProducts[index].unitPrice = safeNumber(selectedProduct.unit_price);
        updatedProducts[index].total = safeNumber(updatedProducts[index].quantity) * safeNumber(selectedProduct.unit_price);
      }
    }

    setSelectedProducts(updatedProducts);
  };

  const calculateTotal = () => {
    return selectedProducts.reduce((sum, product) => sum + (product.total || 0), 0);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!formData.supplier) {
      toast.error("Please select a supplier");
      return;
    }

    if (selectedProducts.length === 0) {
      toast.error("Please add at least one product");
      return;
    }

    setLoading(true);

    try {
      const purchaseOrderData = {
        supplier_id: parseInt(formData.supplier),
        total_amount: calculateTotal(),
        expected_delivery_date: formData.expectedDelivery,
        created_by: currentUser.emp_id,
        products: selectedProducts.map(product => ({
          product_id: parseInt(product.productId),
          quantity: parseInt(product.quantity),
          unit_price: parseFloat(product.unitPrice)
        }))
      };

      const response = await fetch(`${API_BASE_SIMPLE}?action=create_purchase_order`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(purchaseOrderData)
      });

      const result = await response.json();
      
      if (result.success) {
        toast.success(`Purchase Order ${result.po_number} created successfully!`);
        
        // Reset form
        setFormData({
          supplier: "",
          orderDate: new Date().toISOString().split('T')[0],
          expectedDelivery: "",
          notes: "",
        });
        setSelectedProducts([]);
      } else {
        toast.error(result.error || "Error creating purchase order");
      }
      
    } catch (error) {
      toast.error("Error creating purchase order");
      console.error("Error:", error);
    } finally {
      setLoading(false);
    }
  };

  // Purchase Order List functions
  const fetchPurchaseOrders = async () => {
    try {
      const response = await fetch(`${API_BASE_SIMPLE}?action=purchase_orders`);
      const data = await response.json();
      if (data.success) {
        setPurchaseOrders(data.data);
      } else {
        toast.error('Failed to load purchase orders');
      }
    } catch (error) {
      console.error('Error fetching purchase orders:', error);
      toast.error('Error loading purchase orders');
    } finally {
      setPoLoading(false);
    }
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      'pending': { color: 'bg-yellow-100 text-yellow-800', text: 'Pending' },
      'approved': { color: 'bg-green-100 text-green-800', text: 'Approved' },
      'rejected': { color: 'bg-red-100 text-red-800', text: 'Rejected' }
    };
    
    const config = statusConfig[status] || { color: 'bg-gray-100 text-gray-800', text: status };
    return (
      <span className={`px-2 py-1 rounded-full text-xs font-medium ${config.color}`}>
        {config.text}
      </span>
    );
  };

  const getDeliveryStatusBadge = (status) => {
    const statusConfig = {
      'pending': { color: 'bg-yellow-100 text-yellow-800', text: 'Pending' },
      'in_transit': { color: 'bg-blue-100 text-blue-800', text: 'In Transit' },
      'delivered': { color: 'bg-green-100 text-green-800', text: 'Delivered' },
      'partial': { color: 'bg-orange-100 text-orange-800', text: 'Partial' },
      'cancelled': { color: 'bg-red-100 text-red-800', text: 'Cancelled' }
    };
    
    const config = statusConfig[status] || { color: 'bg-gray-100 text-gray-800', text: status };
    return (
      <span className={`px-2 py-1 rounded-full text-xs font-medium ${config.color}`}>
        {config.text}
      </span>
    );
  };

  const handleApprove = async (poId, action) => {
    try {
      const response = await fetch(`${API_BASE_SIMPLE}?action=approve_purchase_order`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          purchase_header_id: poId,
          approved_by: currentUser.emp_id,
          approval_status: action,
          approval_notes: action === 'approved' ? 'Approved by admin' : 'Rejected by admin'
        })
      });

      const result = await response.json();
      
      if (result.success) {
        toast.success(`Purchase Order ${action === 'approved' ? 'approved' : 'rejected'} successfully!`);
        fetchPurchaseOrders(); // Refresh the list
      } else {
        toast.error(result.error || `Error ${action}ing purchase order`);
      }
    } catch (error) {
      toast.error(`Error ${action}ing purchase order`);
      console.error('Error:', error);
    }
  };

  const handleUpdateDelivery = async (poId, status) => {
    try {
      const response = await fetch(`${API_BASE_SIMPLE}?action=update_delivery_status`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          purchase_header_id: poId,
          delivery_status: status,
          actual_delivery_date: status === 'delivered' ? new Date().toISOString().split('T')[0] : null
        })
      });

      const result = await response.json();
      
      if (result.success) {
        toast.success(`Delivery status updated to ${status}!`);
        fetchPurchaseOrders(); // Refresh the list
      } else {
        toast.error(result.error || 'Error updating delivery status');
      }
    } catch (error) {
      toast.error('Error updating delivery status');
      console.error('Error:', error);
    }
  };

  const viewDetails = async (poId) => {
    try {
      const response = await fetch(`${API_BASE_SIMPLE}?action=purchase_order_details&po_id=${poId}`);
      const data = await response.json();
      if (data.success) {
        setSelectedPO(data);
        setShowDetails(true);
      } else {
        toast.error('Failed to load purchase order details');
      }
    } catch (error) {
      console.error('Error fetching PO details:', error);
      toast.error('Error loading purchase order details');
    }
  };

  // Receive Items functions
  const fetchReceivingList = async () => {
    try {
      const response = await fetch(`${API_BASE_SIMPLE}?action=receiving_list`);
      const data = await response.json();
      if (data.success) {
        setReceivingList(data.data);
      } else {
        toast.error('Failed to load receiving list');
      }
    } catch (error) {
      console.error('Error fetching receiving list:', error);
      toast.error('Error loading receiving list');
    } finally {
      setReceiveLoading(false);
    }
  };

  const handleReceive = async (poId) => {
    try {
      // Get PO details for receiving
      const response = await fetch(`${API_BASE}?action=purchase_order_details&po_id=${poId}`);
      const data = await response.json();
      if (data.success) {
        setSelectedPO(data);
        
        // Initialize form data with PO details
        setReceiveFormData({
          delivery_receipt_no: "",
          notes: "",
          items: data.details.map(detail => ({
            product_id: detail.product_id,
            product_name: detail.product_name,
            ordered_qty: detail.quantity,
            received_qty: detail.quantity, // Default to ordered quantity
            unit_price: detail.price,
            batch_number: "",
            expiration_date: ""
          }))
        });
        
        setShowReceiveForm(true);
      } else {
        toast.error('Failed to load purchase order details');
      }
    } catch (error) {
      console.error('Error fetching PO details:', error);
      toast.error('Error loading purchase order details');
    }
  };

  const handleReceiveInputChange = (e) => {
    const { name, value } = e.target;
    setReceiveFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleItemChange = (index, field, value) => {
    const updatedItems = [...receiveFormData.items];
    updatedItems[index] = {
      ...updatedItems[index],
      [field]: value
    };
    setReceiveFormData(prev => ({
      ...prev,
      items: updatedItems
    }));
  };

  const handleSubmitReceive = async (e) => {
    e.preventDefault();
    
    if (!receiveFormData.delivery_receipt_no.trim()) {
      toast.error("Please enter delivery receipt number");
      return;
    }

    const hasItems = receiveFormData.items.some(item => item.received_qty > 0);
    if (!hasItems) {
      toast.error("Please enter received quantities for at least one item");
      return;
    }

    try {
      const receiveData = {
        purchase_header_id: selectedPO.header.purchase_header_id,
        received_by: currentUser.emp_id,
        delivery_receipt_no: receiveFormData.delivery_receipt_no,
        notes: receiveFormData.notes,
        items: receiveFormData.items.filter(item => item.received_qty > 0)
      };

      const response = await fetch(`${API_BASE}?action=receive_items`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(receiveData)
      });

      const result = await response.json();
      
      if (result.success) {
        toast.success(`Items received successfully! Receiving ID: ${result.receiving_id}`);
        setShowReceiveForm(false);
        setSelectedPO(null);
        setReceiveFormData({
          delivery_receipt_no: "",
          notes: "",
          items: []
        });
        fetchReceivingList(); // Refresh the list
      } else {
        toast.error(result.error || "Error receiving items");
      }
    } catch (error) {
      toast.error("Error receiving items");
      console.error("Error:", error);
    }
  };

  // Loading states
  if ((activeTab === 'create' && loading) || (activeTab === 'list' && poLoading) || (activeTab === 'receive' && receiveLoading)) {
    return (
      <div className="p-8">
        <div className="flex justify-center items-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-500"></div>
        </div>
      </div>
    );
  }

  return (
    <div className="p-8 space-y-8">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">PURCHASE ORDER MANAGEMENT</h1>
        <p className="text-gray-600 mt-2">Create, manage, and receive purchase orders</p>
      </div>

      {/* Tab Navigation */}
      <div className="border-b border-gray-200">
        <nav className="-mb-px flex space-x-8">
          <button
            onClick={() => setActiveTab('create')}
            className={`py-2 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'create'
                ? 'border-green-500 text-green-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Create Purchase Order
          </button>
          <button
            onClick={() => setActiveTab('list')}
            className={`py-2 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'list'
                ? 'border-green-500 text-green-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Purchase Orders
          </button>
          <button
            onClick={() => setActiveTab('receive')}
            className={`py-2 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'receive'
                ? 'border-green-500 text-green-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Receive Items
          </button>
        </nav>
      </div>

      {/* Create Purchase Order Tab */}
      {activeTab === 'create' && (
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Basic Information */}
          <div className="bg-white shadow-sm p-6 rounded-lg">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">ORDER INFORMATION</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Supplier *
                </label>
                <select
                  name="supplier"
                  value={formData.supplier}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                  required
                >
                  <option value="">Select a supplier</option>
                  {suppliers.map(supplier => (
                    <option key={supplier.supplier_id} value={supplier.supplier_id}>
                      {supplier.supplier_name} - {supplier.supplier_contact}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Order Date
                </label>
                <input
                  type="date"
                  name="orderDate"
                  value={formData.orderDate}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Expected Delivery
                </label>
                <input
                  type="date"
                  name="expectedDelivery"
                  value={formData.expectedDelivery}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Notes
                </label>
                <textarea
                  name="notes"
                  value={formData.notes}
                  onChange={handleInputChange}
                  rows="3"
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                  placeholder="Additional notes for this order..."
                />
              </div>
            </div>
          </div>

          {/* Products Section */}
          <div className="bg-white shadow-sm p-6 rounded-lg">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-semibold text-gray-900">PRODUCTS</h3>
              <button
                type="button"
                onClick={addProduct}
                className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
              >
                + Add Product
              </button>
            </div>

            {selectedProducts.length === 0 ? (
              <div className="text-center py-8 text-gray-500">
                No products added yet. Click Add `Product` to get started.
              </div>
            ) : (
              <div className="space-y-4">
                {selectedProducts.map((product, index) => (
                  <div key={product.id} className="border border-gray-200 rounded-lg p-4">
                    <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          Product
                        </label>
                        <select
                          value={product.productId}
                          onChange={(e) => updateProduct(index, 'productId', e.target.value)}
                          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                        >
                          <option value="">Select product</option>
                          {products.map(p => (
                            <option key={p.product_id} value={p.product_id}>
                              {p.product_name} - {p.category} ({p.brand || 'No Brand'})
                            </option>
                          ))}
                        </select>
                      </div>

                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          Quantity
                        </label>
                        <input
                          type="number"
                          min="1"
                          value={product.quantity || ''}
                          onChange={(e) => updateProduct(index, 'quantity', e.target.value)}
                          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                        />
                      </div>

                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          Unit Price (₱)
                        </label>
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          value={product.unitPrice || ''}
                          onChange={(e) => updateProduct(index, 'unitPrice', e.target.value)}
                          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                        />
                      </div>

                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          Total (₱)
                        </label>
                        <input
                          type="text"
                          value={product.total || ''}
                          className="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50"
                          readOnly
                        />
                      </div>

                      <div className="flex items-end">
                        <button
                          type="button"
                          onClick={() => removeProduct(index)}
                          className="px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                        >
                          Remove
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Order Summary */}
          {selectedProducts.length > 0 && (
            <div className="bg-white shadow-sm p-6 rounded-lg">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">ORDER SUMMARY</h3>
              <div className="flex justify-between items-center">
                <div>
                  <p className="text-sm text-gray-600">Total Items: {selectedProducts.length}</p>
                  <p className="text-sm text-gray-600">Total Quantity: {selectedProducts.reduce((sum, p) => sum + (p.quantity || 0), 0)}</p>
                </div>
                <div className="text-right">
                  <p className="text-2xl font-bold text-gray-900">
                    Total: ₱{calculateTotal().toFixed(2)}
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Submit Button */}
          <div className="flex justify-end space-x-4">
            <button
              type="button"
              onClick={() => {
                setFormData({
                  supplier: "",
                  orderDate: "",
                  expectedDelivery: "",
                  notes: "",
                });
                setSelectedProducts([]);
              }}
              className="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={loading}
              className="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {loading ? "Creating..." : "Create Purchase Order"}
            </button>
          </div>
        </form>
      )}

      {/* Purchase Orders List Tab */}
      {activeTab === 'list' && (
        <div className="space-y-6">
          <div className="flex justify-between items-center">
            <h2 className="text-2xl font-bold text-gray-900">PURCHASE ORDERS</h2>
            <button
              onClick={fetchPurchaseOrders}
              className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
            >
              Refresh
            </button>
          </div>

          {/* Purchase Orders Table */}
          <div className="bg-white shadow-sm rounded-lg overflow-hidden">
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      PO Number
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Supplier
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Date
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Total Amount
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Delivery
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {purchaseOrders.map((po) => (
                    <tr key={po.purchase_header_id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {po.po_number || `PO-${po.purchase_header_id}`}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {po.supplier_name}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {new Date(po.date).toLocaleDateString()}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ₱{parseFloat(po.total_amount).toFixed(2)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {getStatusBadge(po.status)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {getDeliveryStatusBadge(po.delivery_status)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <button
                          onClick={() => viewDetails(po.purchase_header_id)}
                          className="text-blue-600 hover:text-blue-900"
                        >
                          View
                        </button>
                        
                        {po.status === 'pending' && (
                          <>
                            <button
                              onClick={() => handleApprove(po.purchase_header_id, 'approved')}
                              className="text-green-600 hover:text-green-900"
                            >
                              Approve
                            </button>
                            <button
                              onClick={() => handleApprove(po.purchase_header_id, 'rejected')}
                              className="text-red-600 hover:text-red-900"
                            >
                              Reject
                            </button>
                          </>
                        )}
                        
                        {po.status === 'approved' && po.delivery_status === 'pending' && (
                          <button
                            onClick={() => handleUpdateDelivery(po.purchase_header_id, 'in_transit')}
                            className="text-blue-600 hover:text-blue-900"
                          >
                            Mark In Transit
                          </button>
                        )}
                        
                        {po.delivery_status === 'in_transit' && (
                          <button
                            onClick={() => handleUpdateDelivery(po.purchase_header_id, 'delivered')}
                            className="text-green-600 hover:text-green-900"
                          >
                            Mark Delivered
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* Receive Items Tab */}
      {activeTab === 'receive' && (
        <div className="space-y-6">
          <div className="flex justify-between items-center">
            <h2 className="text-2xl font-bold text-gray-900">RECEIVE ITEMS</h2>
            <button
              onClick={fetchReceivingList}
              className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
            >
              Refresh
            </button>
          </div>

          {/* Receiving List Table */}
          <div className="bg-white shadow-sm rounded-lg overflow-hidden">
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      PO Number
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Supplier
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Expected Delivery
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Total Amount
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Delivery Status
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {receivingList.map((po) => (
                    <tr key={po.purchase_header_id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {po.po_number || `PO-${po.purchase_header_id}`}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {po.supplier_name}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {po.expected_delivery_date ? 
                          new Date(po.expected_delivery_date).toLocaleDateString() : 'Not set'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ₱{parseFloat(po.total_amount).toFixed(2)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                          po.delivery_status === 'delivered' ? 'bg-green-100 text-green-800' :
                          po.delivery_status === 'partial' ? 'bg-orange-100 text-orange-800' :
                          'bg-yellow-100 text-yellow-800'
                        }`}>
                          {po.delivery_status === 'delivered' ? 'Delivered' :
                           po.delivery_status === 'partial' ? 'Partial' : 'Pending'}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button
                          onClick={() => handleReceive(po.purchase_header_id)}
                          className="text-green-600 hover:text-green-900"
                        >
                          Receive Items
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* Purchase Order Details Modal */}
      {showDetails && selectedPO && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-transparent backdrop-blur-sm border-2 border-gray-400">
            <div className="mt-3">
              <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg font-medium text-gray-900">
                  Purchase Order Details - {selectedPO.header.po_number}
                </h3>
                <button
                  onClick={() => setShowDetails(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                  <p className="text-sm text-gray-600">Supplier</p>
                  <p className="font-medium">{selectedPO.header.supplier_name}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Order Date</p>
                  <p className="font-medium">{new Date(selectedPO.header.date).toLocaleDateString()}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Expected Delivery</p>
                  <p className="font-medium">
                    {selectedPO.header.expected_delivery_date ? 
                      new Date(selectedPO.header.expected_delivery_date).toLocaleDateString() : 'Not set'}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Total Amount</p>
                  <p className="font-medium">₱{parseFloat(selectedPO.header.total_amount).toFixed(2)}</p>
                </div>
              </div>

              <div className="mb-6">
                <h4 className="text-md font-medium text-gray-900 mb-3">Order Items</h4>
                <div className="overflow-x-auto">
                  <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                      <tr>
                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                      </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                      {selectedPO.details.map((item, index) => (
                        <tr key={index}>
                          <td className="px-4 py-2 text-sm text-gray-900">{item.product_name}</td>
                          <td className="px-4 py-2 text-sm text-gray-900">{item.quantity}</td>
                          <td className="px-4 py-2 text-sm text-gray-900">₱{parseFloat(item.price).toFixed(2)}</td>
                          <td className="px-4 py-2 text-sm text-gray-900">₱{(item.quantity * item.price).toFixed(2)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>

              <div className="flex justify-end">
                <button
                  onClick={() => setShowDetails(false)}
                  className="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Receive Items Modal */}
      {showReceiveForm && selectedPO && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-transparent backdrop-blur-sm border-2 border-gray-400">
            <div className="mt-3">
              <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg font-medium text-gray-900">
                  Receive Items - {selectedPO.header.po_number}
                </h3>
                <button
                  onClick={() => setShowReceiveForm(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <form onSubmit={handleSubmitReceive} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Delivery Receipt No. *
                    </label>
                    <input
                      type="text"
                      name="delivery_receipt_no"
                      value={receiveFormData.delivery_receipt_no}
                      onChange={handleReceiveInputChange}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Notes
                    </label>
                    <input
                      type="text"
                      name="notes"
                      value={receiveFormData.notes}
                      onChange={handleReceiveInputChange}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                </div>

                <div className="mt-6">
                  <h4 className="text-md font-medium text-gray-900 mb-3">Items to Receive</h4>
                  <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gray-50">
                        <tr>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ordered Qty</th>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Received Qty</th>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Batch No.</th>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Exp. Date</th>
                        </tr>
                      </thead>
                      <tbody className="bg-white divide-y divide-gray-200">
                        {receiveFormData.items.map((item, index) => (
                          <tr key={index}>
                            <td className="px-4 py-2 text-sm text-gray-900">{item.product_name}</td>
                            <td className="px-4 py-2 text-sm text-gray-900">{item.ordered_qty}</td>
                            <td className="px-4 py-2">
                              <input
                                type="number"
                                min="0"
                                max={item.ordered_qty}
                                value={item.received_qty}
                                onChange={(e) => handleItemChange(index, 'received_qty', parseInt(e.target.value))}
                                className="w-20 px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                              />
                            </td>
                            <td className="px-4 py-2 text-sm text-gray-900">₱{parseFloat(item.unit_price).toFixed(2)}</td>
                            <td className="px-4 py-2">
                              <input
                                type="text"
                                value={item.batch_number}
                                onChange={(e) => handleItemChange(index, 'batch_number', e.target.value)}
                                className="w-24 px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                placeholder="Batch #"
                              />
                            </td>
                            <td className="px-4 py-2">
                              <input
                                type="date"
                                value={item.expiration_date}
                                onChange={(e) => handleItemChange(index, 'expiration_date', e.target.value)}
                                className="w-32 px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                              />
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>

                <div className="flex justify-end space-x-3 pt-4">
                  <button
                    type="button"
                    onClick={() => setShowReceiveForm(false)}
                    className="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                  >
                    Receive Items
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default CreatePurchaseOrder; 