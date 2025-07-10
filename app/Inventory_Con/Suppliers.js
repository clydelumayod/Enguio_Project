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
  Edit,
  Trash2,
} from "lucide-react";

function Suppliers() {
  const [suppliers, setSuppliers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showAddModal, setShowAddModal] = useState(false);
  const [selectedSupplier, setSelectedSupplier] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");

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

  const API_BASE_URL = "http://localhost/capstone_api/backend.php";

  // API function
  async function handleApiCall(action, data = {}) {
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

  const loadSuppliers = async () => {
    setLoading(true);
    try {
      const response = await handleApiCall("get_suppliers");
      if (response.success && Array.isArray(response.data)) {
        setSuppliers(response.data);
      } else {
        setSuppliers([]);
      }
    } catch (error) {
      console.error("Error loading suppliers:", error);
      toast.error("Failed to load suppliers");
      setSuppliers([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadSuppliers();
  }, []);

  const handleAddSupplier = async (e) => {
    e.preventDefault();
    setLoading(true);
    
    try {
      const response = await handleApiCall("add_supplier", supplierFormData);
      if (response.success) {
        toast.success("Supplier added successfully!");
        setShowAddModal(false);
        clearSupplierForm();
        loadSuppliers();
      } else {
        toast.error(response.message || "Failed to add supplier");
      }
    } catch (error) {
      console.error("Error adding supplier:", error);
      toast.error("Failed to add supplier");
    } finally {
      setLoading(false);
    }
  };

  const clearSupplierForm = () => {
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
    });
  };

  const handleSupplierInputChange = (field, value) => {
    setSupplierFormData((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  const filteredSuppliers = suppliers.filter((supplier) =>
    supplier.supplier_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    supplier.supplier_contact?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    supplier.supplier_email?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="p-8">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Suppliers Management</h1>
        <p className="text-gray-600">Manage your suppliers and vendor information</p>
      </div>

      {/* Search and Add Button */}
      <div className="flex justify-between items-center mb-6">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
          <input
            type="text"
            placeholder="Search suppliers..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="pl-10 pr-4 py-2 w-64 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <button
          onClick={() => setShowAddModal(true)}
          className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center gap-2"
        >
          <Plus className="h-4 w-4" />
          Add Supplier
        </button>
      </div>

      {/* Suppliers Table */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Supplier Name
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Contact
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Email
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Contact Person
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Payment Terms
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Lead Time
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {loading ? (
                <tr>
                  <td colSpan={7} className="px-6 py-4 text-center text-gray-500">
                    Loading suppliers...
                  </td>
                </tr>
              ) : filteredSuppliers.length > 0 ? (
                filteredSuppliers.map((supplier) => (
                  <tr key={supplier.supplier_id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {supplier.supplier_name}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {supplier.supplier_contact}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {supplier.supplier_email}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {supplier.contact_person || "-"}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {supplier.payment_terms || "-"}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {supplier.lead_time_days ? `${supplier.lead_time_days} days` : "-"}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      <div className="flex items-center space-x-2">
                        <button className="text-blue-500 hover:text-blue-700">
                          <Edit className="h-4 w-4" />
                        </button>
                        <button className="text-red-500 hover:text-red-700">
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={7} className="px-6 py-4 text-center text-gray-500">
                    No suppliers found
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Add Supplier Modal */}
      {showAddModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
              <h3 className="text-lg font-semibold text-gray-900">Add New Supplier</h3>
              <button onClick={() => setShowAddModal(false)} className="text-gray-400 hover:text-gray-600">
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
                  <label className="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                  <input
                    type="text"
                    value={supplierFormData.contact_person}
                    onChange={(e) => handleSupplierInputChange("contact_person", e.target.value)}
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
                  onClick={() => setShowAddModal(false)}
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

      <ToastContainer />
    </div>
  );
}

export default Suppliers;