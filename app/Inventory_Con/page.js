"use client";

import React, { useState } from "react";
import { useRouter } from "next/navigation";
import Sidebar from "./sidebar";
import Dashboard from "./Dashboard";
import InventoryTransfer from "./InventoryTransfer";
import Warehouse from "./Warehouse";
import ConvenienceInventory from "./ConvenienceStore";
import MovementHistory from "./MovementHistory";
import PharmacyInventory from "./PharmacyInventory";
import StockAdjustment from "./StockAdjustment";
import Reports from "./Reports";
import Settings from "./Settings";
import { ToastContainer, toast } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import Archive from "./Archive";
import CreatePurchaseOrder from "./CreatePurchaseOrder";
import LogoutConfirm from "./LogoutConfirm";

export default function Page() {
  const [selectedFeature, setSelectedFeature] = useState("Dashboard");
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);
  const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);
  const router = useRouter();

  const handleLogout = () => {
    // Clear any session data or localStorage if needed
    localStorage.removeItem('user_session');
    sessionStorage.clear();
    
    // Show logout message
    toast.success("Successfully logged out!", {
      position: "top-right",
      autoClose: 2000,
      hideProgressBar: false,
      closeOnClick: true,
      pauseOnHover: true,
      draggable: true,
    });

    // Redirect to login page after a short delay
    setTimeout(() => {
      router.push("/");
    }, 1000);
  };

  const handleLogoutClick = () => {
    setShowLogoutConfirm(true);
  };

  const handleLogoutConfirm = () => {
    setShowLogoutConfirm(false);
    handleLogout();
  };

  const handleLogoutCancel = () => {
    setShowLogoutConfirm(false);
  };

  const handleFeatureSelect = (feature) => {
    if (feature === "Logout") {
      handleLogoutClick();
    } else {
      setSelectedFeature(feature);
    }
  };

  const componentMap = {
    "Dashboard": Dashboard,
    "Inventory Transfer": InventoryTransfer,
    "ConvenienceInventory": ConvenienceInventory,
    "PharmacyInventory": PharmacyInventory,
    "Warehouse Inventory": Warehouse,
    "StockAdjustment": StockAdjustment,
    "MovementHistory": MovementHistory,
    "Create Purchase Order": CreatePurchaseOrder,
    "Reports": Reports,
    "Settings": Settings,
    "Archive": Archive,
  };
  
  // Get the component to render
  const ComponentToRender = componentMap[selectedFeature] || Dashboard;

  return (
    <>
      <div className="flex h-screen bg-gray-50">
        {/* Sidebar */}
        <Sidebar
          onSelectFeature={handleFeatureSelect}
          selectedFeature={selectedFeature}
          isSidebarOpen={isSidebarOpen}
          setIsSidebarOpen={setIsSidebarOpen}
        />
        
        {/* Main Content Area */}
        <main
          className={`flex-1 p-8 overflow-y-auto bg-white transition-all duration-300 ease-in-out ${
            isSidebarOpen ? "ml-64" : "ml-20"
          }`}
        >
          <ComponentToRender />
        </main>
      </div>
      
      {/* Logout Confirmation Modal */}
      {showLogoutConfirm && (
        <LogoutConfirm
          onConfirm={handleLogoutConfirm}
          onCancel={handleLogoutCancel}
        />
      )}
      
      <ToastContainer />
    </>
  );
} 