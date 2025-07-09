"use client";
import React, { useState } from 'react';
import { Tooltip } from '@heroui/tooltip'; // Corrected import

const Sidebar = ({ onSelectFeature, selectedFeature,isSidebarOpen,setIsSidebarOpen }) => {
  // Start collapsed by default

  return (
    <div
      className={`fixed top-0 left-0 h-full transition-all duration-300 ease-in-out bg-teal-600 text-white p-4 z-10 ${
        isSidebarOpen ? "w-64" : "w-18"
      }`}
    >
      {/* Burger Icon - Always on top */}
      <button
  onClick={() => {
  
    setIsSidebarOpen(!isSidebarOpen);
    // Log the new state
  }}
  className="absolute top-3 right-3 p-2 ml-0"
  aria-label="Toggle Sidebar"
>
  
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-7 h-7">
          <path
            fillRule="evenodd"
            d="M3 6.75A.75.75 0 013.75 6h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 6.75zM3 12a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5h-16.5A.75.75 0 013 12zm0 5.25a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5h-16.5a.75.75 0 01-.75-.75z"
            clipRule="evenodd"
          />
        </svg>
      </button>

      {/* Logo Section - Only shown when sidebar is open */}
      {isSidebarOpen && (
        <div className="flex items-center gap-2 mb-6 mt-3  hover:cursor-pointer">
          <span className="text-xl font-bold">Enguio Logo</span>
        </div>
      )}

      {/* Navigation Section */}
      <div className="space-y-1 mt-10 overflow-y-auto">
        <ul className="space-y-1">
          {/* Dashboard */}
          <li>
            <Tooltip content="Dashboard" placement="right" className={`bg-black text-white w-20 rounded ${
              isSidebarOpen ? 'hidden' : ''
            }`}>
              <button
                onClick={() => onSelectFeature('Dashboard')}
                className={`block px-1 py-2 rounded hover:bg-teal-700 w-full text-left text-lg flex items-center gap-3 ${
                  selectedFeature === 'Dashboard' ? 'bg-teal-700' : ''
                }`}
              >
                <img src='/assets/dashboard.png' alt="Dashboard" className="w-7 h-7" />
                <span className={`${!isSidebarOpen ? 'hidden' : 'inline'}`}>Dashboard</span>
              </button>
            </Tooltip>
          </li>

          {/* Products */}
          <li>
            <Tooltip content="Products" placement="right" className={`bg-black text-white w-17 rounded
              ${isSidebarOpen ? 'hidden' : ''}`}>
              <button
                onClick={() => onSelectFeature('products')}
                className={`block px-1 py-2 rounded hover:bg-teal-700 w-full text-left text-lg flex items-center gap-3 ${
                  selectedFeature === 'products' ? 'bg-teal-700' : ''
                }`}
              >
                <img src='/assets/box.png' alt="Products" className="w-7 h-7" />
                <span className={`${!isSidebarOpen ? 'hidden' : 'inline'}`}>Products</span>
              </button>
            </Tooltip>
          </li>

          {/* Vendor */}
          <li>
            <Tooltip content="Supplier" placement="right" className={`bg-black text-white w-14 rounded
              ${isSidebarOpen ? 'hidden':''}`}>
              <button
                onClick={() => onSelectFeature('Supplier')}
                className={`block px-1 py-2 rounded hover:bg-teal-700 w-full text-left text-lg flex items-center gap-3 ${
                  selectedFeature === 'Vendor' ? 'bg-teal-700' : ''
                }`}
              >
                <img src='/assets/vendor.png' alt="Supplier" className="w-7 h-7" />
                <span className={`${!isSidebarOpen ? 'hidden' : 'inline'}`}>Supplier</span>
              </button>
            </Tooltip>
          </li>

          {/* Category */}
         

          {/* Brand */}
          <li>
            <Tooltip content="Brand" placement="right" className={`bg-black text-white w-20 rounded
              ${isSidebarOpen?'hidden':''}`}>
              <button
                onClick={() => onSelectFeature('Brand')}
                className={`block px-1 py-2 rounded hover:bg-teal-700 w-full text-left text-lg flex items-center gap-3 ${
                  selectedFeature === 'Brand' ? 'bg-teal-700' : ''
                }`}
              >
                <img src='/assets/brand-image.png' alt="Brand" className="w-7 h-7" />
                <span className={`${!isSidebarOpen ? 'hidden' : 'inline'}`}>Brand</span>
              </button>
            </Tooltip>
          </li>

          {/* Records */}
          <li>
            <Tooltip content="Records" placement="right" className={`bg-black text-white w-20 rounded
              ${isSidebarOpen?'hidden':''}`}>
              <button
                onClick={() => onSelectFeature('Records')}
                className={`block px-1 py-2 rounded hover:bg-teal-700 w-full text-left text-lg flex items-center gap-3 ${
                  selectedFeature === 'Records' ? 'bg-teal-700' : ''
                }`}
              >
                <img src='/assets/edit.png' alt="Records" className="w-7 h-7" />
                <span className={`${!isSidebarOpen ? 'hidden' : 'inline'}`}>Records</span>
              </button>
            </Tooltip>
          </li>

          {/* Sales History */}
          <li>
            <Tooltip content="Sales History" placement="right" className={`bg-black text-white w-25 rounded
              ${isSidebarOpen?'hidden':''}`}>
              <button
                onClick={() => onSelectFeature('Sales History')}
                className={`block px-1 py-2 rounded hover:bg-teal-700 w-full text-left text-lg flex items-center gap-3 ${
                  selectedFeature === 'Sales History' ? 'bg-teal-700' : ''
                }`}
              >
                <img src='/assets/history.png' alt="Sales History" className="w-7 h-7" />
                <span className={`${!isSidebarOpen ? 'hidden' : 'inline'}`}>Sales History</span>
              </button>
            </Tooltip>
          </li>

          {/* Store Settings */}
          <li>
            <Tooltip content="Store Settings" placement="right" className={`bg-black text-white w-27 rounded
              ${isSidebarOpen?'hidden':''}`}>
              <button
                onClick={() => onSelectFeature('Store Settings')}
                className={`block px-1 py-2 rounded hover:bg-teal-700 w-full text-left text-lg flex items-center gap-3 ${
                  selectedFeature === 'Store Settings' ? 'bg-teal-700' : ''
                }`}
              >
                <img src='/assets/settings.png' alt="Store Settings" className="w-7 h-7" />
                <span className={`${!isSidebarOpen ? 'hidden' : 'inline'}`}>Store Settings</span>
              </button>
            </Tooltip>
          </li>

          {/* User */}
          <li>
            <Tooltip content="User" placement="right" className={`bg-black text-white w-20 rounded
              ${isSidebarOpen?'hidden':''}`}>
              <button
                onClick={() => onSelectFeature('User')}
                className={`block px-1 py-2 rounded hover:bg-teal-700 w-full text-left text-lg flex items-center gap-3 ${
                  selectedFeature === 'User' ? 'bg-teal-700' : ''
                }`}
              >
                <img src='/assets/user.png' alt="User" className="w-7 h-7" />
                <span className={`${!isSidebarOpen ? 'hidden' : 'inline'}`}>User</span>
              </button>
            </Tooltip>
          </li>

          {/* Logout */}
          <li>
            <Tooltip content="Logout" placement="right" className={`bg-black text-white w-20 rounded
              ${isSidebarOpen?'hidden':''}`}>
              <button
                onClick={() => onSelectFeature('Logout')}
                className={`block px-1 py-2 rounded hover:bg-teal-700 w-full text-left text-lg flex items-center gap-3 ${
                  selectedFeature === 'Logout' ? 'bg-teal-700' : ''
                }`}
              >
                <img src='/assets/logout.png' alt="Logout" className="w-7 h-7" />
                <span className={`${!isSidebarOpen ? 'hidden' : 'inline'}`}>Logout</span>
              </button>
            </Tooltip>
          </li>
        </ul>
      </div>
    </div>
  );
};

export default Sidebar;