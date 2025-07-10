"use client";

import React from "react";

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

export default Dashboard;