"use client";
import React, { useState } from "react";
import { toast, ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import { FaSave, FaCog, FaBell, FaShieldAlt, FaUser, FaDatabase, FaPalette, FaGlobe, FaKey, FaEye, FaEyeSlash } from "react-icons/fa";
import { Settings as SettingsIcon, Bell, Shield, Database, Palette, Key } from "lucide-react";

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
      toast.success('Settings saved successfully!');
      setTimeout(() => setSaveStatus(""), 3000);
    }, 1000);
  };

  const handleChangePassword = async () => {
    if (passwords.newPassword !== passwords.confirmPassword) {
      toast.error("New passwords do not match!");
      return;
    }
    if (passwords.newPassword.length < 8) {
      toast.error("Password must be at least 8 characters long!");
      return;
    }
    setIsLoading(true);
    setTimeout(() => {
      setIsLoading(false);
      setPasswords({ currentPassword: "", newPassword: "", confirmPassword: "" });
      toast.success("Password changed successfully!");
    }, 1000);
  };

  const getSaveStatusColor = () => {
    switch (saveStatus) {
      case "saving": return "bg-yellow-100 text-yellow-800";
      case "saved": return "bg-green-100 text-green-800";
      default: return "bg-gray-100 text-gray-800";
    }
  };

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Settings</h1>
          <p className="text-gray-600">Manage system settings and preferences</p>
        </div>
        <div className="flex gap-3">
          {saveStatus && (
            <span className={`inline-flex px-3 py-1 text-sm font-medium rounded-full ${getSaveStatusColor()}`}>
              {saveStatus === "saving" ? "Saving..." : "Settings saved!"}
            </span>
          )}
          <button 
            onClick={handleSaveSettings}
            disabled={isLoading}
            className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            <FaSave className="h-4 w-4" />
            Save Settings
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* General Settings */}
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center gap-3 mb-6">
            <SettingsIcon className="h-6 w-6 text-blue-500" />
            <h3 className="text-xl font-semibold text-gray-900">General Settings</h3>
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
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center gap-3 mb-6">
            <Bell className="h-6 w-6 text-yellow-500" />
            <h3 className="text-xl font-semibold text-gray-900">Notification Settings</h3>
          </div>
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-700">Email Notifications</span>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.emailNotifications}
                  onChange={(e) => handleSettingChange("emailNotifications", e.target.checked)}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-700">SMS Notifications</span>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.smsNotifications}
                  onChange={(e) => handleSettingChange("smsNotifications", e.target.checked)}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>
            <hr className="my-4" />
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-700">Low Stock Alerts</span>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.lowStockAlerts}
                  onChange={(e) => handleSettingChange("lowStockAlerts", e.target.checked)}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-700">Expiry Date Alerts</span>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.expiryAlerts}
                  onChange={(e) => handleSettingChange("expiryAlerts", e.target.checked)}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-700">Movement Alerts</span>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.movementAlerts}
                  onChange={(e) => handleSettingChange("movementAlerts", e.target.checked)}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>
          </div>
        </div>

        {/* Security Settings */}
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center gap-3 mb-6">
            <Shield className="h-6 w-6 text-red-500" />
            <h3 className="text-xl font-semibold text-gray-900">Security Settings</h3>
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
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-700">Require Password Change Every 90 Days</span>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.requirePasswordChange}
                  onChange={(e) => handleSettingChange("requirePasswordChange", e.target.checked)}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-700">Enable Two-Factor Authentication</span>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.twoFactorAuth}
                  onChange={(e) => handleSettingChange("twoFactorAuth", e.target.checked)}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>
          </div>
        </div>

        {/* Inventory Settings */}
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center gap-3 mb-6">
            <Database className="h-6 w-6 text-green-500" />
            <h3 className="text-xl font-semibold text-gray-900">Inventory Settings</h3>
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
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-700">Enable Auto Reorder</span>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.autoReorder}
                  onChange={(e) => handleSettingChange("autoReorder", e.target.checked)}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-700">Enable Barcode Scanning</span>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.barcodeScanning}
                  onChange={(e) => handleSettingChange("barcodeScanning", e.target.checked)}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>
          </div>
        </div>

        {/* Display Settings */}
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center gap-3 mb-6">
            <Palette className="h-6 w-6 text-purple-500" />
            <h3 className="text-xl font-semibold text-gray-900">Display Settings</h3>
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
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-700">Compact Mode</span>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.compactMode}
                  onChange={(e) => handleSettingChange("compactMode", e.target.checked)}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-700">Show Product Images</span>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.showImages}
                  onChange={(e) => handleSettingChange("showImages", e.target.checked)}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>
          </div>
        </div>

        {/* Change Password */}
        <div className="bg-white rounded-3xl shadow-xl p-6">
          <div className="flex items-center gap-3 mb-6">
            <Key className="h-6 w-6 text-blue-500" />
            <h3 className="text-xl font-semibold text-gray-900">Change Password</h3>
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
                  className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                  onClick={() => togglePasswordVisibility("current")}
                >
                  {showPasswords.current ? <FaEyeSlash className="h-4 w-4" /> : <FaEye className="h-4 w-4" />}
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
                  className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                  onClick={() => togglePasswordVisibility("new")}
                >
                  {showPasswords.new ? <FaEyeSlash className="h-4 w-4" /> : <FaEye className="h-4 w-4" />}
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
                  className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                  onClick={() => togglePasswordVisibility("confirm")}
                >
                  {showPasswords.confirm ? <FaEyeSlash className="h-4 w-4" /> : <FaEye className="h-4 w-4" />}
                </button>
              </div>
            </div>
            <button
              onClick={handleChangePassword}
              disabled={isLoading}
              className="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
            >
              {isLoading ? 'Changing Password...' : 'Change Password'}
            </button>
          </div>
        </div>
      </div>

      <ToastContainer
        position="top-right"
        autoClose={3000}
        hideProgressBar={false}
        newestOnTop={false}
        closeOnClick
        rtl={false}
        pauseOnFocusLoss
        draggable
        pauseOnHover
      />
    </div>
  );
};

export default Settings; 