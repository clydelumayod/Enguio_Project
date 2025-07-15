"use client";
import React, { useState } from "react";
import { Button, Input, Switch, Select, SelectItem, Divider, Chip } from "@nextui-org/react";
import { FaSave, FaCog, FaBell, FaShieldAlt, FaUser, FaDatabase, FaPalette, FaGlobe, FaKey, FaEye, FaEyeSlash } from "react-icons/fa";

const Settings = () => {
  const [settings, setSettings] = useState({
    companyName: "Enguio Pharmacy",
    systemLanguage: "en",
    timezone: "Asia/Manila",
    dateFormat: "MM/DD/YYYY",
    currency: "PHP",
    emailNotifications: true,
    smsNotifications: false,
    lowStockAlerts: true,
    expiryAlerts: true,
    movementAlerts: true,
    sessionTimeout: 30,
    requirePasswordChange: false,
    twoFactorAuth: false,
    loginAttempts: 3,
    lowStockThreshold: 10,
    autoReorder: false,
    expiryWarningDays: 30,
    barcodeScanning: true,
    theme: "light",
    compactMode: false,
    showImages: true,
    itemsPerPage: 20
  });
  const [passwords, setPasswords] = useState({
    currentPassword: "",
    newPassword: "",
    confirmPassword: ""
  });
  const [showPasswords, setShowPasswords] = useState({
    current: false,
    new: false,
    confirm: false
  });
  const [isLoading, setIsLoading] = useState(false);
  const [saveStatus, setSaveStatus] = useState("");
  const languages = [
    { key: "en", label: "English" },
    { key: "tl", label: "Tagalog" },
    { key: "es", label: "Spanish" }
  ];
  const timezones = [
    { key: "Asia/Manila", label: "Philippines (GMT+8)" },
    { key: "UTC", label: "UTC (GMT+0)" },
    { key: "America/New_York", label: "Eastern Time (GMT-5)" }
  ];
  const dateFormats = [
    { key: "MM/DD/YYYY", label: "MM/DD/YYYY" },
    { key: "DD/MM/YYYY", label: "DD/MM/YYYY" },
    { key: "YYYY-MM-DD", label: "YYYY-MM-DD" }
  ];
  const currencies = [
    { key: "PHP", label: "Philippine Peso (₱)" },
    { key: "USD", label: "US Dollar ($)" },
    { key: "EUR", label: "Euro (€)" }
  ];
  const themes = [
    { key: "light", label: "Light" },
    { key: "dark", label: "Dark" },
    { key: "auto", label: "Auto" }
  ];
  const handleSettingChange = (key, value) => {
    setSettings(prev => ({ ...prev, [key]: value }));
  };
  const handlePasswordChange = (key, value) => {
    setPasswords(prev => ({ ...prev, [key]: value }));
  };
  const togglePasswordVisibility = (field) => {
    setShowPasswords(prev => ({ ...prev, [field]: !prev[field] }));
  };
  const handleSaveSettings = async () => {
    setIsLoading(true);
    setSaveStatus("saving");
    setTimeout(() => {
      setIsLoading(false);
      setSaveStatus("saved");
      setTimeout(() => setSaveStatus(""), 3000);
    }, 1000);
  };
  const handleChangePassword = async () => {
    if (passwords.newPassword !== passwords.confirmPassword) {
      alert("New passwords do not match!");
      return;
    }
    if (passwords.newPassword.length < 8) {
      alert("Password must be at least 8 characters long!");
      return;
    }
    setIsLoading(true);
    setTimeout(() => {
      setIsLoading(false);
      setPasswords({ currentPassword: "", newPassword: "", confirmPassword: "" });
      alert("Password changed successfully!");
    }, 1000);
  };
  const getSaveStatusColor = () => {
    switch (saveStatus) {
      case "saving": return "warning";
      case "saved": return "success";
      default: return "default";
    }
  };
  return (
    <div className="min-h-screen bg-gray-50 p-6">
      {/* Header */}
      <div className="mb-8 flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Settings</h1>
          <p className="text-gray-600">Manage system settings and preferences</p>
        </div>
        <div className="flex gap-3">
          {saveStatus && (
            <Chip color={getSaveStatusColor()} variant="flat">
              {saveStatus === "saving" ? "Saving..." : "Settings saved!"}
            </Chip>
          )}
          <Button 
            color="primary" 
            startContent={<FaSave />}
            onPress={handleSaveSettings}
            isLoading={isLoading}
          >
            Save Settings
          </Button>
        </div>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* General Settings */}
        <div className="bg-white rounded-lg shadow-md border border-gray-200 p-6 mb-6">
          <div className="flex items-center gap-3 mb-4">
            <FaCog className="text-blue-500" />
            <h3 className="text-xl font-bold text-gray-900">General Settings</h3>
          </div>
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
              <input
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                value={settings.companyName}
                onChange={(e) => handleSettingChange("companyName", e.target.value)}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">System Language</label>
              <select
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                value={settings.systemLanguage}
                onChange={(e) => handleSettingChange("systemLanguage", e.target.value)}
              >
                {languages.map((lang) => (
                  <option key={lang.key} value={lang.key}>{lang.label}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
              <select
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                value={settings.timezone}
                onChange={(e) => handleSettingChange("timezone", e.target.value)}
              >
                {timezones.map((tz) => (
                  <option key={tz.key} value={tz.key}>{tz.label}</option>
                ))}
              </select>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Date Format</label>
                <select
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value={settings.dateFormat}
                  onChange={(e) => handleSettingChange("dateFormat", e.target.value)}
                >
                  {dateFormats.map((format) => (
                    <option key={format.key} value={format.key}>{format.label}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                <select
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value={settings.currency}
                  onChange={(e) => handleSettingChange("currency", e.target.value)}
                >
                  {currencies.map((currency) => (
                    <option key={currency.key} value={currency.key}>{currency.label}</option>
                  ))}
                </select>
              </div>
            </div>
          </div>
        </div>
        {/* Notification Settings */}
        <div className="bg-white rounded-lg shadow-md border border-gray-200 p-6 mb-6">
          <div className="flex items-center gap-3 mb-4">
            <FaBell className="text-yellow-500" />
            <h3 className="text-xl font-bold text-gray-900">Notification Settings</h3>
          </div>
          <div className="space-y-4">
            <div className="flex items-center gap-2">
              <Switch
                isSelected={settings.emailNotifications}
                onValueChange={(value) => handleSettingChange("emailNotifications", value)}
              />
              <span>Email Notifications</span>
            </div>
            <div className="flex items-center gap-2">
              <Switch
                isSelected={settings.smsNotifications}
                onValueChange={(value) => handleSettingChange("smsNotifications", value)}
              />
              <span>SMS Notifications</span>
            </div>
            <Divider />
            <div className="flex items-center gap-2">
              <Switch
                isSelected={settings.lowStockAlerts}
                onValueChange={(value) => handleSettingChange("lowStockAlerts", value)}
              />
              <span>Low Stock Alerts</span>
            </div>
            <div className="flex items-center gap-2">
              <Switch
                isSelected={settings.expiryAlerts}
                onValueChange={(value) => handleSettingChange("expiryAlerts", value)}
              />
              <span>Expiry Date Alerts</span>
            </div>
            <div className="flex items-center gap-2">
              <Switch
                isSelected={settings.movementAlerts}
                onValueChange={(value) => handleSettingChange("movementAlerts", value)}
              />
              <span>Movement Alerts</span>
            </div>
          </div>
        </div>
        {/* Security Settings */}
        <div className="bg-white rounded-lg shadow-md border border-gray-200 p-6 mb-6">
          <div className="flex items-center gap-3 mb-4">
            <FaShieldAlt className="text-red-500" />
            <h3 className="text-xl font-bold text-gray-900">Security Settings</h3>
          </div>
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Session Timeout (minutes)</label>
                <input
                  type="number"
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value={settings.sessionTimeout}
                  onChange={(e) => handleSettingChange("sessionTimeout", parseInt(e.target.value))}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Max Login Attempts</label>
                <input
                  type="number"
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value={settings.loginAttempts}
                  onChange={(e) => handleSettingChange("loginAttempts", parseInt(e.target.value))}
                />
              </div>
            </div>
            <div className="flex items-center gap-2">
              <Switch
                isSelected={settings.requirePasswordChange}
                onValueChange={(value) => handleSettingChange("requirePasswordChange", value)}
              />
              <span>Require Password Change Every 90 Days</span>
            </div>
            <div className="flex items-center gap-2">
              <Switch
                isSelected={settings.twoFactorAuth}
                onValueChange={(value) => handleSettingChange("twoFactorAuth", value)}
              />
              <span>Enable Two-Factor Authentication</span>
            </div>
          </div>
        </div>
        {/* Inventory Settings */}
        <div className="bg-white rounded-lg shadow-md border border-gray-200 p-6 mb-6">
          <div className="flex items-center gap-3 mb-4">
            <FaDatabase className="text-green-500" />
            <h3 className="text-xl font-bold text-gray-900">Inventory Settings</h3>
          </div>
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Low Stock Threshold</label>
              <input
                type="number"
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                value={settings.lowStockThreshold}
                onChange={(e) => handleSettingChange("lowStockThreshold", parseInt(e.target.value))}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Expiry Warning Days</label>
              <input
                type="number"
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                value={settings.expiryWarningDays}
                onChange={(e) => handleSettingChange("expiryWarningDays", parseInt(e.target.value))}
              />
            </div>
            <div className="flex items-center gap-2">
              <Switch
                isSelected={settings.autoReorder}
                onValueChange={(value) => handleSettingChange("autoReorder", value)}
              />
              <span>Enable Auto Reorder</span>
            </div>
            <div className="flex items-center gap-2">
              <Switch
                isSelected={settings.barcodeScanning}
                onValueChange={(value) => handleSettingChange("barcodeScanning", value)}
              />
              <span>Enable Barcode Scanning</span>
            </div>
          </div>
        </div>
        {/* Display Settings */}
        <div className="bg-white rounded-lg shadow-md border border-gray-200 p-6 mb-6">
          <div className="flex items-center gap-3 mb-4">
            <FaPalette className="text-purple-500" />
            <h3 className="text-xl font-bold text-gray-900">Display Settings</h3>
          </div>
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Theme</label>
              <select
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                value={settings.theme}
                onChange={(e) => handleSettingChange("theme", e.target.value)}
              >
                {themes.map((theme) => (
                  <option key={theme.key} value={theme.key}>{theme.label}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Items Per Page</label>
              <input
                type="number"
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                value={settings.itemsPerPage}
                onChange={(e) => handleSettingChange("itemsPerPage", parseInt(e.target.value))}
              />
            </div>
            <div className="flex items-center gap-2">
              <Switch
                isSelected={settings.compactMode}
                onValueChange={(value) => handleSettingChange("compactMode", value)}
              />
              <span>Compact Mode</span>
            </div>
            <div className="flex items-center gap-2">
              <Switch
                isSelected={settings.showImages}
                onValueChange={(value) => handleSettingChange("showImages", value)}
              />
              <span>Show Product Images</span>
            </div>
          </div>
        </div>
        {/* Change Password */}
        <div className="bg-white rounded-lg shadow-md border border-gray-200 p-6 mb-6">
          <div className="flex items-center gap-3 mb-4">
            <FaKey className="text-blue-500" />
            <h3 className="text-xl font-bold text-gray-900">Change Password</h3>
          </div>
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
              <div className="relative">
                <input
                  type={showPasswords.current ? "text" : "password"}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10"
                  value={passwords.currentPassword}
                  onChange={(e) => handlePasswordChange("currentPassword", e.target.value)}
                />
                <button
                  type="button"
                  className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500"
                  onClick={() => togglePasswordVisibility("current")}
                >
                  {showPasswords.current ? <FaEyeSlash /> : <FaEye />}
                </button>
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">New Password</label>
              <div className="relative">
                <input
                  type={showPasswords.new ? "text" : "password"}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10"
                  value={passwords.newPassword}
                  onChange={(e) => handlePasswordChange("newPassword", e.target.value)}
                />
                <button
                  type="button"
                  className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500"
                  onClick={() => togglePasswordVisibility("new")}
                >
                  {showPasswords.new ? <FaEyeSlash /> : <FaEye />}
                </button>
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
              <div className="relative">
                <input
                  type={showPasswords.confirm ? "text" : "password"}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10"
                  value={passwords.confirmPassword}
                  onChange={(e) => handlePasswordChange("confirmPassword", e.target.value)}
                />
                <button
                  type="button"
                  className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500"
                  onClick={() => togglePasswordVisibility("confirm")}
                >
                  {showPasswords.confirm ? <FaEyeSlash /> : <FaEye />}
                </button>
              </div>
            </div>
            <Button
              color="primary"
              onPress={handleChangePassword}
              isLoading={isLoading}
              className="w-full mt-2"
            >
              Change Password
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Settings; 