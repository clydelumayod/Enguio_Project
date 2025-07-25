"use client";

import React, { useState } from "react";
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
import { ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import Archive from "./Archive";
import CreatePurchaseOrder from "./CreatePurchaseOrder";

export default function Page() {
  const [selectedFeature, setSelectedFeature] = useState("Dashboard");
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);

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
          onSelectFeature={setSelectedFeature}
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
      <ToastContainer />
    </>
  );
} 