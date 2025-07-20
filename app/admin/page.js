"use client";
import React, { useState, useEffect } from "react";
import axios from "axios";
import Sidebar from '../components/sidebars';
import { toast, ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";


const API_BASE_URL = "http://localhost/enguio/Api/backend.php";
//dashboard
function Dashboard() {
  const metrics = [
    {
      title: "TOTAL SALES",
      value: "₱24,780",
      subtitle: "+8% from last month",
      icon: "💰", // Using emoji instead of lucide icon
      trend: "up",
    },
    {
      title: "ACTIVE SUPPLIERS",
      value: "10",
      subtitle: "+20% from last month",
      icon: "👥", // Using emoji instead of lucide icon
      trend: "up",
    },
    {
      title: "TOTAL PRODUCTS",
      value: "1,284",
      subtitle: "+4% from last month",
      icon: "📦", // Using emoji instead of lucide icon
      trend: "up",
    },
    {
      title: "AVERAGE TIME",
      value: "3.2h",
      subtitle: "-5% from last month",
      icon: "⏰", // Using emoji instead of lucide icon
      trend: "down",
    },
  ];

  const recentActivities = [
    {
      title: "New shipment received - 8:00 AM",
      color: "bg-green-500",
    },
    {
      title: "Inventory count updated - 3:30 AM",
      color: "bg-blue-500",
    },
    {
      title: "Low stock alert: Paracetamol 500mg - 8:15 AM",
      color: "bg-yellow-500",
    },
    {
      title: "Stock transfer completed - Yesterday",
      color: "bg-purple-500",
    },
    {
      title: "Inventory adjustment approved - Yesterday",
      color: "bg-red-500",
    },
  ];

  const quickActions = [
    {
      title: "ADD PRODUCTS",
      icon: "➕", // Using emoji instead of lucide icon
      color: "bg-gray-100 hover:bg-gray-200",
    },
    {
      title: "STOCKS RECEIVING",
      icon: "📈", // Using emoji instead of lucide icon
      color: "bg-blue-100 hover:bg-blue-200",
    },
    {
      title: "REPORTS",
      icon: "📊", // Using emoji instead of lucide icon
      color: "bg-gray-100 hover:bg-gray-200",
    },
    {
      title: "STOCK COUNT",
      icon: "📋", // Using emoji instead of lucide icon
      color: "bg-orange-100 hover:bg-orange-200",
    },
  ];

  return (
    <div className="p-8 space-y-8">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">DASHBOARD</h1>
      </div>

      {/* Metrics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {metrics.map((metric, index) => (
          <div key={index} className="bg-white shadow-sm p-6 rounded-lg">
            <div className="flex flex-row items-center justify-between space-y-0 pb-2">
              <h3 className="text-sm font-medium text-gray-600">{metric.title}</h3>
              <span className="text-lg">{metric.icon}</span>
            </div>
            <div>
              <div className="text-2xl font-bold text-gray-900">{metric.value}</div>
              <p className="text-xs text-gray-600 mt-1">{metric.subtitle}</p>
            </div>
          </div>
        ))}
      </div>

      {/* Recent Activity and Quick Actions */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Recent Activity */}
        <div className="bg-white shadow-sm p-6 rounded-lg">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">RECENT ACTIVITY</h3>
          <div className="space-y-4">
            {recentActivities.map((activity, index) => (
              <div key={index} className="flex items-center space-x-3">
                <div className={`w-3 h-3 rounded-full ${activity.color}`} />
                <span className="text-sm text-gray-700">{activity.title}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Quick Actions */}
        <div className="bg-white shadow-sm p-6 rounded-lg">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">QUICK ACTIONS</h3>
          <div className="grid grid-cols-2 gap-4">
            {quickActions.map((action, index) => (
              <button
                key={index}
                className={`h-20 flex flex-col items-center justify-center space-y-2 ${action.color} border border-gray-200 rounded-lg`}
              >
                <span className="text-2xl">{action.icon}</span>
                <span className="text-xs font-medium">{action.title}</span>
              </button>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
//product
function Products(){
  const [products, setProducts] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchProducts = async () => {
      try {
        const response = await axios.post(API_BASE_URL, {
          action: "get_products"
        });
        if (response.data.success) {
          setProducts(response.data.data || []);
        } else {
          setProducts([]);
        }
      } catch (err) {
        setProducts([]);
      } finally {
        setLoading(false);
      }
    };
    fetchProducts();
  }, []);

  const filteredProducts = products.filter(
    (product) =>
      product.product_name?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="p-8">
      <h1 className="text-2xl font-bold mb-6">Product Management</h1>
      <div className="mb-6">
        <input
          type="text"
          placeholder="Search products by name..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="w-full border p-2 rounded"
        />
      </div>
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="max-h-[500px] overflow-y-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50 sticky top-0 z-10">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-bold uppercase">#</th>
                <th className="px-6 py-3 text-left text-xs font-bold uppercase">Product Name</th>
                <th className="px-6 py-3 text-left text-xs font-bold uppercase">Category</th>
                <th className="px-6 py-3 text-left text-xs font-bold uppercase">Barcode</th>
                <th className="px-6 py-3 text-left text-xs font-bold uppercase">Description</th>
                <th className="px-6 py-3 text-left text-xs font-bold uppercase">Prescription</th>
                <th className="px-6 py-3 text-left text-xs font-bold uppercase">Bulk</th>
                <th className="px-6 py-3 text-left text-xs font-bold uppercase">Expiration</th>
                <th className="px-6 py-3 text-left text-xs font-bold uppercase">Quantity</th>
                <th className="px-6 py-3 text-left text-xs font-bold uppercase">Unit Price</th>
                <th className="px-10 py-3 text-left text-xs font-bold uppercase">Brand</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {loading ? (
                <tr>
                  <td colSpan="11" className="text-center py-4">Loading...</td>
                </tr>
              ) : filteredProducts.length > 0 ? (
                filteredProducts.map((product, index) => (
                  <tr key={product.product_id}>
                    <td className="px-6 py-4">{index + 1}</td>
                    <td className="px-6 py-4">{product.product_name}</td>
                    <td className="px-6 py-4">{product.category}</td>
                    <td className="px-6 py-4">{product.barcode}</td>
                    <td className="px-6 py-4">{product.description}</td>
                    <td className="px-6 py-4">{product.prescription}</td>
                    <td className="px-6 py-4">{product.bulk}</td>
                    <td className="px-6 py-4">{product.expiration}</td>
                    <td className="px-6 py-4">{product.quantity}</td>
                    <td className="px-6 py-4">{product.unit_price}</td>
                    <td className="px-6 py-4">{product.brand}</td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="11" className="text-center py-4">No products found</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );

}

//user
function 
UserManagement() { 
  const [showModal, setShowModal] = useState(false);
  const [formData, setFormData] = useState({
    fname: "",
    mname: "",
    lname: "",
    birthdate: "",
    gender: "",
    username: "",
    password: "",
    contact: "",
    email: "",
    role: "",
    shift: "",
    age: "",
    address: "",
    status: "Active",
  });
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedRole, setSelectedRole] = useState("all");
  const [selectedStatus, setSelectedStatus] = useState("all");

  // State for historical data
  const [prevTotalUsers, setPrevTotalUsers] = useState(0);
  const [prevActiveUsers, setPrevActiveUsers] = useState(0);
  const [prevInactiveUsers, setPrevInactiveUsers] = useState(0);

  // Debugging state
  const [debugInfo, setDebugInfo] = useState("");

  // Fetch employees with status
  useEffect(() => {
    const fetchEmployee = async () => {
      try {
        const response = await axios.post(API_BASE_URL, {
          action: "display_employee",
        });
        if (response.data.success) {
          const userData = (response.data.employees || []).map((user) => ({
            ...user,
            status: user.status || "Active",
            role_id: parseInt(user.role_id),
            shift_id: parseInt(user.shift_id),
          }));
          setUsers(userData);
        } else {
          setError(response.data.message || "Failed to fetch employees");
        }
      } catch (err) {
        console.error("Error fetching employees:", err);
        setError("An error occurred while fetching employees.");
      } finally {
        setLoading(false);
      }
    };

    // Initial fetch
    fetchEmployee();

    // Set up polling every 5 seconds
    const intervalId = setInterval(fetchEmployee, 1000);

    // Cleanup on unmount
    return () => clearInterval(intervalId);
  }, []);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    // Sanitize empty strings to null
    const sanitizedData = {
      ...formData,
      fname: formData.fname.trim() || null,
      mname: formData.mname.trim() || null,
      lname: formData.lname.trim() || null,
    };

    // Only require shift for cashier and pharmacist
    const role = sanitizedData.role;
    const needsShift = role === "cashier" || role === "pharmacist";
    if (needsShift && !sanitizedData.shift) {
      toast.error("Shift is required for Cashier and Pharmacist.", {
        style: { backgroundColor: "red", color: "white" },
        position: "top-right",
        hideProgressBar: true,
        autoClose: 3000,
      });
      return;
    }

    try {
      const payload = {
        action: "add_employee",
        fname: sanitizedData.fname,
        mname: sanitizedData.mname,
        lname: sanitizedData.lname,
        birthdate: sanitizedData.birthdate,
        gender: sanitizedData.gender,
        username: sanitizedData.username,
        password: sanitizedData.password,
        contact_num: sanitizedData.contact,
        email: sanitizedData.email,
        role_id:
          role === "admin"
            ? 1
            : role === "cashier"
            ? 3
            : role === "inventory"
            ? 4
            : 2,
        age: sanitizedData.age,
        address: sanitizedData.address,
        status: sanitizedData.status,
      };
      // Only include shift_id for cashier and pharmacist
      if (needsShift) {
        payload.shift_id =
          sanitizedData.shift === "Shift1"
            ? 1
            : sanitizedData.shift === "Shift2"
            ? 2
            : sanitizedData.shift === "Shift3"
            ? 3
            : null;
      }

      const response = await axios.post(API_BASE_URL, payload);

      if (response.data.success) {
        toast.success("Employee added successfully!", {
          style: { backgroundColor: "green", color: "white" },
          position: "top-right",
          hideProgressBar: true,
          autoClose: 3000,
        });
        setUsers([
          ...users,
          {
            ...sanitizedData,
            role_id:
              role === "admin"
                ? 1
                : role === "cashier"
                ? 3
                : role === "inventory"
                ? 4
                : 2,
            shift_id: needsShift
              ? sanitizedData.shift === "Shift1"
                ? 1
                : sanitizedData.shift === "Shift2"
                ? 2
                : sanitizedData.shift === "Shift3"
                ? 3
                : null
              : null,
          },
        ]);
        setFormData({
          fname: "",
          mname: "",
          lname: "",
          birthdate: "",
          gender: "",
          username: "",
          password: "",
          contact: "",
          email: "",
          role: "",
          shift: "",
          age: "",
          address: "",
          status: "Active",
        });
        setShowModal(false);
      } else {
        toast.error(response.data.message || "Failed to add employee.", {
          style: { backgroundColor: "red", color: "white" },
          position: "top-right",
          hideProgressBar: true,
          autoClose: 3000,
        });
      }
    } catch (error) {
      toast.error(error.response?.data?.message || "An error occurred.", {
        style: { backgroundColor: "red", color: "white" },
        position: "top-right",
        hideProgressBar: true,
        autoClose: 3000,
      });
    }
  };

  const handleStatusChange = async (employeeId, newStatus) => {
    const idToUse = Number(employeeId); // Ensure numeric emp_id
    console.log("Debug: Updating user ID:", idToUse, "to status:", newStatus);
    try {
      const response = await axios.post(API_BASE_URL, {
        action: "update_employee_status",
        id: idToUse,
        status: newStatus,
      });
      console.log("Debug: API Response Status:", response.status);
      console.log("Debug: API Response Data:", response.data);
      if (response.data.success) {
        toast.success("Status updated successfully!",
          {
            style:{backgroundColor:"green", color:"white"},
            position:"top-right",
            hideProgressBar:true,
            autoClose:3000
          }
        );
        setUsers((prevUsers) =>
          prevUsers.map((user) =>
            Number(user.emp_id) === idToUse ? { ...user, status: newStatus } : user
          )
        );
      } else {
        toast.error(response.data.message || "Failed to update status.",
          {
            style:{backgroundColor:"red", color:"white"},
            position:"top-right",
            hideProgressBar:true,
            autoClose:3000
          }
        );
      }
    } catch (error) {
      console.error("Debug: Error updating status:", error);
      toast.error(error.response?.data?.message || "An error occurred.",
        {
            style:{backgroundColor:"red", color:"white"},
            position:"top-right",
            hideProgressBar:true,
            autoClose:3000
          }
      );
    }
  };

  const filteredUsers = users.filter((user) => {
    // Debugging: Log user data once
    if (users.length > 0 && !debugInfo) {
      setDebugInfo(
        `Role type: ${typeof user.role_id}, Role value: ${user.role_id}, Shift type: ${typeof user.shift_id}, Shift value: ${user.shift_id}`
      );
    }

    const matchesSearch =
      (user.Fname || "").toLowerCase().includes(searchQuery.toLowerCase()) ||
      (user.Lname || "").toLowerCase().includes(searchQuery.toLowerCase()) ||
      (user.email || "").toLowerCase().includes(searchQuery.toLowerCase());

    const matchesRole =
      selectedRole === "all" ||
      (selectedRole === "admin" && user.role_id === 1) ||
      (selectedRole === "cashier" && user.role_id === 3) ||
      (selectedRole === "pharmacist" && user.role_id === 2) ||
      (selectedRole === "inventory" && user.role_id === 4);

    const matchesStatus =
      selectedStatus === "all" ||
      (selectedStatus === "Active" && user.status === "Active") ||
      (selectedStatus === "Inactive" && user.status === "Inactive");

    return matchesSearch && matchesRole && matchesStatus;
  });

  // Calculate user statistics based on filtered results
  const totalUsers = filteredUsers.length;
  const activeUsers = filteredUsers.filter((user) => user.status === "Active").length;
  const inactiveUsers = filteredUsers.filter((user) => user.status === "Inactive").length;

  // Calculate percentage changes
  const calculateChange = (current, previous) => {
    if (previous === 0) return current > 0 ? "100%" : "0%";
    const change = ((current - previous) / previous) * 100;
    return `${change.toFixed(1)}%`;
  };

  const totalChange = calculateChange(totalUsers, prevTotalUsers);
  const activeChange = calculateChange(activeUsers, prevActiveUsers);
  const inactiveChange = calculateChange(inactiveUsers, prevInactiveUsers);

  // Updated Card Component for horizontal stretching
  const UserCard = ({ title, count, change }) => {
    const isPositive = parseFloat(change) > 0;
    const changeText = `${isPositive ? "+" : ""}`;
    return (
      <div className="bg-white p-6 rounded-lg shadow-md w-full h-32 flex flex-col justify-center">
        <h3 className="text-lg font-semibold">{title}</h3>
        <div className="text-4xl font-bold mt-2">{count}</div>
        <div className={`text-sm mt-2 ${isPositive ? "text-green-500" : "text-green-500"}`}>
          {changeText}
        </div>
      </div>
    );
  };

  // Shift mapping function
  const getShiftName = (shiftId) => {
    switch (shiftId) {
      case 1:
        return "Shift1";
      case 2:
        return "Shift2";
      case 3:
        return "Shift3";
      default:
        return "Unknown";
    }
  };

  // Format date helper
  const formatDate = (dateString) => {
    if (!dateString) return "-";
    const options = { day: "2-digit", month: "2-digit", year: "numeric" };
    return new Date(dateString).toLocaleDateString("en-GB", options);
  };

  return (
    <div className="p-8">
      <h1 className="text-2xl font-bold mb-4">User Management</h1>

      {/* Horizontally stretched cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <UserCard title="Total Users" count={totalUsers} />
        <UserCard title="Active Users" count={activeUsers} />
        <UserCard title="Inactive Users" count={inactiveUsers} />
      </div>

      {/* Existing Controls */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <p className="text-sm text-gray-500">Manage your user accounts and permissions</p>
        </div>
        <div className="flex items-center space-x-4">
          <input
            type="text"
            placeholder="Search users..."
            className="px-4 py-2 border rounded-lg w-64"
            onChange={(e) => setSearchQuery(e.target.value)}
          />
          <select
            value={selectedRole}
            onChange={(e) => setSelectedRole(e.target.value)}
            className="px-4 py-2 border rounded-lg"
          >
            <option value="all">All roles</option>
            <option value="admin">Admin</option>
            <option value="cashier">Cashier</option>
            <option value="pharmacist">Pharmacist</option>
            <option value="inventory">Inventory</option>
          </select>
          <select
            value={selectedStatus}
            onChange={(e) => setSelectedStatus(e.target.value)}
            className="px-4 py-2 border rounded-lg"
          >
            <option value="all">All statuses</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
          <button
            onClick={() => setShowModal(true)}
            className="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600"
          >
            Add User
          </button>
        </div>
      </div>

      {/* Scrollable Table Container */}
      <div className="max-h-[300px] overflow-y-auto border border-gray-200 rounded mt-4">
        <table className="w-full border-collapse">
          <thead>
            <tr className="bg-gray-100 sticky top-0">
              <th className="py-2">#</th>
              <th className="py-2">User</th>
              <th className="py-2">Birthdate</th>
              <th className="py-2">Contact</th>
              <th className="py-2">Username</th>
              <th className="py-2">Gender</th>
              <th className="py-2">Role</th>
              <th className="py-2">Shift</th>
              <th className="py-2 pl-5">Age</th>
              <th className="py-2 pl-5">Address</th>
              <th className="py-2">Status</th>
            </tr>
          </thead>
          <tbody>
            {filteredUsers.map((user, index) => (
              <tr key={index} className="border-b">
                <td className="py-2 pl-2">{index + 1}</td>
                <td className="py-2 pl-2">
                  <div className="flex items-center">
                    <div className="w-8 h-8 rounded-full bg-gray-200 mr-2 flex items-center justify-center">
                      {`${user.Fname?.[0] || ""}${user.Lname?.[0] || ""}`.toUpperCase()}
                    </div>
                    <div>
                      <p className="font-semibold pl-5">
                        {`${user.Fname || ""} ${user.Mname || ""} ${user.Lname || ""}`.trim()}
                      </p>
                      <p className="text-sm text-blue-500 pl-5">{user.email || "-"}</p>
                    </div>
                  </div>
                </td>
                <td className="py-2 pl-2">{formatDate(user.birthdate)}</td>
                <td className="py-2 pl-2">{user.contact_num ?? "-"}</td>
                <td className="py-2 pl-3">{user.username ?? "-"}</td>
                <td className="py-2 pl-3">{user.gender ?? "-"}</td>
                <td className="py-2 pl-2">
                  <span className="px-2 py-1 bg-green-100 text-green-700 rounded-lg">
                    {user.role_id === 1
                      ? "Admin"
                      : user.role_id === 2
                      ? "Pharmacist"
                      : user.role_id === 3
                      ? "Cashier"
                      : user.role_id === 4
                      ? "Inventory"
                      : "Unknown"}
                  </span>
                </td>
                <td className="py-2 pl-2">
                     {(user.role_id === 2 || user.role_id === 3) ? (
                       <span className="px-2 py-1 bg-blue-100 text-blue-700 rounded-lg">
                        {getShiftName(user.shift_id)}
                   </span>
                         ) : (
                          "-"
                           )}
                  </td>
                <td className="py-2 pl-5">{user.age ?? "-"}</td>
                <td className="py-2 pl-15">{user.address ?? "-"}</td>
                <td className="py-2 pl-1">
                  <select
                    value={user.status || "Active"}
                    onChange={(e) => handleStatusChange(user.emp_id, e.target.value)}
                    className={`px-2 py-1 rounded-lg ${
                      user.status === "Active"
                        ? "bg-green-100 text-green-700"
                        : "bg-red-200 text-red-700"
                    }`}
                  >
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-transparent backdrop-blur-sm p-6 rounded shadow-lg w-full max-w-md mx-auto border-2 border-gray-400">
            <h2 className="text-xl font-semibold mb-4">Add New Employee</h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              {/* First, Middle, Last Name */}
              <div className="grid grid-cols-3 gap-4">
                <div>
                  <label className="block mb-1">First Name</label>
                  <input
                    type="text"
                    name="fname"
                    value={formData.fname}
                    onChange={handleInputChange}
                    className="w-full border p-2 rounded"
                    required
                  />
                </div>
                <div>
                  <label className="block mb-1">Middle Name</label>
                  <input
                    type="text"
                    name="mname"
                    value={formData.mname}
                    onChange={handleInputChange}
                    className="w-full border p-2 rounded"
                  />
                </div>
                <div>
                  <label className="block mb-1">Last Name</label>
                  <input
                    type="text"
                    name="lname"
                    value={formData.lname}
                    onChange={handleInputChange}
                    className="w-full border p-2 rounded"
                    required
                  />
                </div>
                <div>
                  <label className="block mb-1">Gender</label>
                  <select
                    name="gender"
                    value={formData.gender}
                    onChange={handleInputChange}
                    className="w-50 border p-2 rounded"
                    required
                  >
                    <option>-- Select Gender --</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                  </select>
                </div>
                <div className="ml-20">
                  <label>Birthdate</label>
                  <input
                    type="date"
                    name="birthdate"
                    min="1800-01-01"
                    value={formData.birthdate}
                    onChange={handleInputChange}
                    className="w-50 border p-2 mr-40 pl-3 rounded"
                    required
                  />
                </div>
              </div>

              {/* Email & Contact */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block mb-1">Email</label>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleInputChange}
                    className="w-full border p-2 rounded"
                    required
                  />
                </div>
                <div>
                  <label className="block mb-1">Contact Number</label>
                  <input
                    type="tel"
                    name="contact"
                    value={formData.contact}
                    onChange={handleInputChange}
                    className="w-full border p-2 rounded"
                    required
                  />
                </div>
              </div>

              {/* Role & Shift */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block mb-1">Role</label>
                  <select
                    name="role"
                    value={formData.role}
                    onChange={handleInputChange}
                    className="w-full border p-2 rounded"
                    required
                  >
                    <option value="">-- Select Role --</option>
                    <option value="admin">Admin</option>
                    <option value="cashier">Cashier</option>
                    <option value="pharmacist">Pharmacist</option>
                    <option value="inventory">Inventory</option>
                  </select>
                </div>
                {(formData.role === "cashier" || formData.role === "pharmacist") && (
                  <div>
                    <label className="block mb-1">Shift</label>
                    <select
                      name="shift"
                      value={formData.shift}
                      onChange={handleInputChange}
                      className="w-full border p-2 rounded"
                      required
                    >
                      <option value="">-- Select Shift --</option>
                      <option value="Shift1">Shift1</option>
                      <option value="Shift2">Shift2</option>
                      <option value="Shift3">Shift3</option>
                    </select>
                  </div>
                )}
              </div>

              {/* Age & Address */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block mb-1">Age</label>
                  <input
                    type="number"
                    name="age"
                    value={formData.age}
                    onChange={handleInputChange}
                    className="w-full border p-2 rounded"
                    required
                  />
                </div>
                <div>
                  <label className="block mb-1">Address</label>
                  <input
                    type="text"
                    name="address"
                    value={formData.address}
                    onChange={handleInputChange}
                    className="w-full border p-2 rounded"
                    required
                  />
                </div>
              </div>

              {/* Username & Password */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block mb-1">Username</label>
                  <input
                    type="text"
                    name="username"
                    value={formData.username}
                    onChange={handleInputChange}
                    className="w-full border p-2 rounded"
                    required
                  />
                </div>
                <div>
                  <label className="block mb-1">Password</label>
                  <input
                    type="password"
                    name="password"
                    value={formData.password}
                    onChange={handleInputChange}
                    className="w-full border p-2 rounded"
                    required
                  />
                </div>
              </div>

              {/* Submit Button */}
              <div className="flex justify-end space-x-2 mt-4">
                <button
                  type="button"
                  onClick={() => setShowModal(false)}
                  className="px-4 py-2 bg-gray-300 rounded"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
                >
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      <ToastContainer/>
    </div>
  );
}
//brand
function BrandManagement() {
  const [brands, setBrands] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [loading, setLoading] = useState(true);

  // Fetch Brands
  useEffect(() => {
    const fetchBrands = async () => {
      try {
        const response = await axios.post(API_BASE_URL, {
          action: 'displayBrand'
        });

        if (response.data.success) {
          setBrands(response.data.brand || []);
        } else {
          console.error(response.data.message || 'Failed to fetch brands');
        }
      } catch (err) {
        console.error('Error fetching brands:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchBrands();
  }, []);

  // Filter Brands
  const filteredBrands = brands
    .filter((brand) => brand && typeof brand.brand_id === 'number')
    .filter((brand) =>
      brand.brand?.toLowerCase().includes(searchTerm.toLowerCase())
    );

  return (
    <div className="p-8">
      <h1 className="text-2xl font-bold mb-6">Brand Management</h1>

      {/* Search Only */}
      <div className="flex justify-between items-center p-4 border-b border-gray-200">
        <h2 className="text-xl font-semibold">Brand List</h2>
        <div className="relative w-1/3">
          <input
            type="text"
            placeholder="Search brands..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 pl-10"
          />
          <svg
            xmlns="http://www.w3.org/2000/svg"
            className="h-5 w-5 text-gray-500 absolute left-3 top-2.5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
        </div>
      </div>

      {/* Brands Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="max-h-[200px] overflow-y-auto border border-gray-200 rounded mt-4">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50 sticky top-0">
              <tr>
                <th scope="col" className="px-6 py-3 text-left text-xs font-bold text-black-700 uppercase tracking-wider">#</th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-bold text-black-700 uppercase tracking-wider">Brand Name</th>
                <th scope="col" className="px-6 py-3 text-center text-xs font-bold text-black-700 uppercase tracking-wider">Products</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {loading ? (
                <tr>
                  <td colSpan="3" className="px-6 py-4 text-center">Loading...</td>
                </tr>
              ) : filteredBrands.length === 0 ? (
                <tr>
                  <td colSpan="3" className="px-6 py-4 text-center text-sm text-gray-500">No brands found</td>
                </tr>
              ) : (
                filteredBrands.map((brand, index) => (
                  <tr key={brand.brand_id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">{index + 1}</td>
                    <td className="px-6 py-4 whitespace-nowrap">{brand.brand}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-center">{brand.product_count ?? 0}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

//supplier
function Supplier() {
  const [suppliers, setSuppliers] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [loading, setLoading] = useState(true);

  // Fetch suppliers on load
  useEffect(() => {
    fetchSuppliers();
  }, []);

  const fetchSuppliers = async () => {
    try {
      const response = await axios.post(
        API_BASE_URL,
        {
          action: "get_suppliers"
        },
        {
          headers: {
            "Content-Type": "application/json"
          }
        }
      );

      if (response.data.success) {
        setSuppliers(response.data.data || []);
      } else {
        console.error(response.data.message || "Failed to fetch suppliers");
      }
    } catch (err) {
      console.error("Error fetching suppliers:", err);
    } finally {
      setLoading(false);
    }
  };

  // Filter suppliers based on search term
  const filteredSuppliers = suppliers.filter(
    (supplier) =>
      supplier &&
      supplier.supplier_name?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="p-8">
      <h1 className="text-2xl font-bold mb-6">Supplier Management</h1>

      {/* Search Box Only */}
      <div className="mb-6">
        <input
          type="text"
          placeholder="Search suppliers by name..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="w-full border p-2 rounded"
        />
      </div>

      {/* Suppliers Table */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="max-h-[500px] overflow-y-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50 sticky top-0 z-10">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-bold text-black-700 uppercase tracking-wider">#</th>
                <th className="px-6 py-3 text-left text-xs font-bold text-black-700 uppercase tracking-wider">Supplier Name</th>
                <th className="px-6 py-3 text-left text-xs font-bold text-black-700 uppercase tracking-wider">Address</th>
                <th className="px-6 py-3 text-left text-xs font-bold text-black-700 uppercase tracking-wider">Contact</th>
                <th className="px-6 py-3 text-left text-xs font-bold text-black-700 uppercase tracking-wider">Email</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredSuppliers.length > 0 ? (
                filteredSuppliers.map((supplier, index) => (
                  <tr key={supplier.supplier_id}>
                    <td className="px-6 py-4">{index + 1}</td>
                    <td className="px-6 py-4">{supplier.supplier_name}</td>
                    <td className="px-6 py-4">{supplier.supplier_address}</td>
                    <td className="px-6 py-4">{supplier.supplier_contact}</td>
                    <td className="px-6 py-4">{supplier.supplier_email}</td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="5" className="text-center py-4">
                    No suppliers found
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}



// Default Export
export default function Admin() {
  const [selectedFeature, setSelectedFeature] = useState("Dashboard");
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);

  const renderContent = () => {
    switch (selectedFeature) {
      //user
      case "User":
        return <UserManagement />;
      //dashboard
      case "Dashboard":
        return <Dashboard />;
      //products
      case "products":
        return <Products/>;
        //supplier
      case "Supplier":
        return <Supplier/>;
        //stock entry
      case "Brand":
        return <BrandManagement />;
        //records
      case "Records":
        return (
          <div className="p-8">
            <h1 className="text-2xl font-bold">Records</h1>
            <p>View or edit records here.</p>
          </div>
        );
        //sales history
      case "Sales History":
        return (
          <div className="p-8">
            <h1 className="text-2xl font-bold">Sales History</h1>
            <p>Review past sales data here.</p>
          </div>
        );
        //store settings
      case "Store Settings":
        return (
          <div className="p-8">
            <h1 className="text-2xl font-bold">Store Settings</h1>
            <p>Configure store settings here.</p>
          </div>
        );
        //logout
      case "Logout":
        return (
          <div className="p-8">
            <h1 className="text-2xl font-bold">Logged Out</h1>
            <p>You have been successfully logged out.</p>
          </div>
        );
      default:
        return (
          <div className="p-8">
            <p>Select a valid feature from the sidebar.</p>
          </div>
        );
    }
  };

  return (
    <>
      <div className="flex h-screen bg-gray-50">
        {/* Sidebar */}
        <Sidebar
          onSelectFeature={setSelectedFeature}
          selectedFeature={selectedFeature}
          isSidebarOpen={isSidebarOpen}
          setIsSidebarOpen={setIsSidebarOpen}
        />
        {/* Main Content Area */}
        <main
          className={`flex-1 p-8 overflow-y-auto bg-white transition-all duration-300 ease-in-out ${
            isSidebarOpen ? "ml-64" : "ml-16"
          }`}
        >
          {renderContent()}
        </main>
      </div>
      <ToastContainer />
    </>
  );
}