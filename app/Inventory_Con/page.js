"use client";

import React, { useState } from "react";
import Sidebar from "./sidebar";
import Dashboard from "./Dashboard";
import InventoryTransfer from "./InventoryTransfer";
import Warehouse from "./Warehouse";
import ConvenienceInventory from "./ConvenienceStore";
import Suppliers from "./Suppliers";
import MovementHistory from "./MovementHistory";
import { ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";

export default function Page() {
  const [selectedFeature, setSelectedFeature] = useState("Dashboard");
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);

  // Component mapping object instead of switch case
  const componentMap = {
    "Dashboard": Dashboard,
    "Inventory Transfer": InventoryTransfer,
    "ConvenienceInventory": ConvenienceInventory,
    "PharmacyInventory": () => (
      <div className="p-8">
        <h1 className="text-2xl font-bold">Pharmacy Inventory</h1>
        <p>Pharmacy inventory management.</p>
      </div>
    ),
    "Warehouse Inventory": Warehouse,
    "Stock Adjustment": () => (
      <div className="p-8">
        <h1 className="text-2xl font-bold">Stock Adjustment</h1>
        <p>Stock adjustment functionality.</p>
      </div>
    ),
    "MovementHistory": () => (
      <MovementHistory />
    ),
    "Reports": () => (
      <div className="p-8">
        <h1 className="text-2xl font-bold">Reports</h1>
        <p>Generate and view reports.</p>
      </div>
    ),
    "Settings": () => (
      <div className="p-8">
        <h1 className="text-2xl font-bold">Settings</h1>
        <p>Configure system settings.</p>
      </div>
    ),
    "Suppliers": Suppliers,
    "Archive": () => (
      <div className="p-8">
        <h1 className="text-2xl font-bold">Archive</h1>
        <p>This is the archive section.</p>
      </div>
    ),
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
            isSidebarOpen ? "ml-64" : "ml-16"
          }`}
        >
          <ComponentToRender />
        </main>
      </div>
      <ToastContainer />
    </>
  );
} 