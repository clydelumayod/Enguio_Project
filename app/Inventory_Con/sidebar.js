"use client";
import React, { useState } from "react";
import { Tooltip } from "@heroui/tooltip";
import {
  FaTachometerAlt,
  FaBoxOpen,
  FaUser,
  FaSignOutAlt,
  FaCog,
  FaTruck,
  FaClipboardList,
  FaTags,
  FaChartLine,
  FaHistory,
  FaBoxes,
} from "react-icons/fa";

const Sidebar = ({
  onSelectFeature,
  selectedFeature,
  isSidebarOpen,
  setIsSidebarOpen,
}) => {
  const [isInventoryDropdownOpen, setIsInventoryDropdownOpen] = useState(false);

  // Features except inventory dropdown
  const features = [
    { label: "Dashboard", icon: <FaTachometerAlt />, key: "Dashboard" },
    { label: "Warehouse Inventory", icon: <FaTags />, key: "Warehouse Inventory" },
    { label: "Convenience Inventory", icon: <FaTruck />, key: "ConvenienceInventory" },
    { label: "Pharmacy Inventory", icon: <FaBoxes />, key: "PharmacyInventory" },
    { label: "Stock Adjustment", icon: <FaClipboardList />, key: "StockAdjustment" },
    { label: "Movement History", icon: <FaHistory />, key: "MovementHistory" },
    { label: "Create Purchase Order", icon: <FaUser />, key: "Create Purchase Order" },
    { label: "Reports", icon: <FaChartLine />, key: "Reports" },
    { label: "Settings", icon: <FaCog />, key: "Settings" },
    { label: "Archive", icon: <FaUser />, key: "Archive" },
    { label: "Logout", icon: <FaSignOutAlt />, key: "Logout" },
  ];

  return (
    <div
      className={`fixed top-0 left-0 h-full transition-all duration-300 ease-in-out bg-white text-gray-800 p-4 z-10 border-r border-gray-200 ${
        isSidebarOpen ? "w-64" : "w-20"
      }`}
    >
      {/* Toggle Button */}
      <button
        onClick={() => setIsSidebarOpen(!isSidebarOpen)}
        className="absolute top-3 right-3 p-2 text-gray-600 hover:text-gray-800"
        aria-label="Toggle Sidebar"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          className="w-7 h-7"
        >
          <path
            fillRule="evenodd"
            d="M3 6.75A.75.75 0 013.75 6h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 6.75zM3 12a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5h-16.5A.75.75 0 013 12zm0 5.25a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5h-16.5a.75.75 0 01-.75-.75z"
            clipRule="evenodd"
          />
        </svg>
      </button>

      {/* Profile Section */}
      {isSidebarOpen && (
        <div className="flex flex-col items-center mt-6 mb-6">
          <div className="w-24 h-24 rounded-full border border-gray-300 overflow-hidden">
            <img
              src="https://ui-avatars.com/api/?name=Elmer+Enguio&background=2f855a&color=ffffff&size=128"
              alt="Avatar"
              className="w-24 h-24 rounded-full object-cover"
            />
          </div>
          <div className="text-xl font-bold font-serif uppercase text-gray-900">ELMER ENGUIO</div>
          <div className="italic font-serif text-lg text-gray-600">Inventory Manager</div>
        </div>
      )}

      {/* Navigation */}
      <div className="space-y-1 overflow-y-auto">
        <ul className="space-y-1 text-base">

          {/* Dashboard (First) */}
          <li key="Dashboard">
            <Tooltip
              content="Dashboard"
              placement="right"
              className={`bg-black text-white rounded ${
                isSidebarOpen ? "hidden" : ""
              }`}
            >
              <button
                onClick={() => onSelectFeature("Dashboard")}
                className={`flex items-center gap-3 px-2 py-2 rounded hover:bg-gray-100 w-full text-left transition-colors ${
                  selectedFeature === "Dashboard" ? "bg-blue-100 text-blue-600 font-semibold" : ""
                }`}
              >
                <span className="text-xl"><FaTachometerAlt /></span>
                <span className={`${!isSidebarOpen ? "hidden" : "inline"}`}>
                  Dashboard
                </span>
              </button>
            </Tooltip>
          </li>

          {/* Inventory Dropdown */}
          <li>
            <button
              onClick={() => setIsInventoryDropdownOpen(!isInventoryDropdownOpen)}
              className={`flex items-center gap-3 px-2 py-2 rounded hover:bg-gray-100 w-full text-left transition-colors ${
                selectedFeature === "Inventory" ? "bg-blue-100 text-blue-600 font-semibold" : ""
              }`}
            >
              <span className="text-xl"><FaBoxOpen /></span>
              <span className={`${!isSidebarOpen ? "hidden" : "inline"}`}>
                Inventory
              </span>
            </button>

            {/* Inventory Dropdown Menu */}
            {isInventoryDropdownOpen && (
              <ul className="pl-4 space-y-1">
                <li>
                  <button
                    onClick={() => onSelectFeature("Inventory Transfer")}
                    className={`flex items-center gap-3 px-2 py-2 rounded hover:bg-gray-100 w-full text-left transition-colors ${
                      selectedFeature === "Inventory Transfer" ? "bg-blue-100 text-blue-600 font-semibold" : ""
                    }`}
                  >
                    <span className="text-xl"><FaTruck /></span>
                    <span className={`${!isSidebarOpen ? "hidden" : "inline"}`}>
                      Inventory Transfer
                    </span>
                  </button>
                </li>
              </ul>
            )}
          </li>

          {/* Other Features */}
          {features
            .filter((item) => item.key !== "Dashboard")
            .map((item) => (
              <li key={item.key}>
                <Tooltip
                  content={item.label}
                  placement="right"
                  className={`bg-black text-white rounded ${
                    isSidebarOpen ? "hidden" : ""
                  }`}
                >
                  <button
                    onClick={() => onSelectFeature(item.key)}
                    className={`flex items-center gap-3 px-2 py-2 rounded hover:bg-gray-100 w-full text-left transition-colors ${
                      selectedFeature === item.key ? "bg-blue-100 text-blue-600 font-semibold" : ""
                    }`}
                  >
                    <span className="text-xl">{item.icon}</span>
                    <span className={`${!isSidebarOpen ? "hidden" : "inline"}`}>
                      {item.label}
                    </span>
                  </button>
                </Tooltip>
              </li>
            ))}
        </ul>
      </div>
    </div>
  );
};

export default Sidebar;
