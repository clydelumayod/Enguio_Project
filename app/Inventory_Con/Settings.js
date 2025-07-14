"use client";
import React, { useState, useEffect } from "react";
import { Card, CardBody, CardHeader, Button, Input, Switch, Select, SelectItem, Textarea, Divider, Chip } from "@nextui-org/react";
import { FaSave, FaCog, FaBell, FaShield, FaUser, FaDatabase, FaPalette, FaGlobe, FaKey, FaEye, FaEyeSlash } from "react-icons/fa";

const Settings = () => {
  const [settings, setSettings] = useState({
    // General Settings
    companyName: "Enguio Pharmacy",
    systemLanguage: "en",
    timezone: "Asia/Manila",
    dateFormat: "MM/DD/YYYY",
    currency: "PHP",
    
    // Notification Settings
    emailNotifications: true,
    smsNotifications: false,
    lowStockAlerts: true,
    expiryAlerts: true,
    movementAlerts: true,
    
    // Security Settings
    sessionTimeout: 30,
    requirePasswordChange: false,
    twoFactorAuth: false,
    loginAttempts: 3,
    
    // Inventory Settings
    lowStockThreshold: 10,
    autoReorder: false,
    expiryWarningDays: 30,
    barcodeScanning: true,
    
    // Display Settings
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
    setSettings(prev => ({
      ...prev,
      [key]: value
    }));
  };

  const handlePasswordChange = (key, value) => {
    setPasswords(prev => ({
      ...prev,
      [key]: value
    }));
  };

  const togglePasswordVisibility = (field) => {
    setShowPasswords(prev => ({
      ...prev,
      [field]: !prev[field]
    }));
  };

  const handleSaveSettings = async () => {
    setIsLoading(true);
    setSaveStatus("saving");
    
    // Simulate API call
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
    // Simulate password change
    setTimeout(() => {
      setIsLoading(false);
      setPasswords({
        currentPassword: "",
        newPassword: "",
        confirmPassword: ""
      });
      alert("Password changed successfully!");
    }, 1000);
  };

  const getSaveStatusColor = () => {
    switch (saveStatus) {
      case "saving":
        return "warning";
      case "saved":
        return "success";
      default:
        return "default";
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

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* General Settings */}
        <Card>
          <CardHeader>
            <div className="flex items-center gap-3">
              <FaCog className="text-blue-500" />
              <h3 className="text-xl font-semibold">General Settings</h3>
            </div>
          </CardHeader>
          <CardBody className="space-y-4">
            <Input
              label="Company Name"
              value={settings.companyName}
              onChange={(e) => handleSettingChange("companyName", e.target.value)}
              startContent={<FaUser className="text-gray-400" />}
            />
            
            <Select
              label="System Language"
              selectedKeys={[settings.systemLanguage]}
              onChange={(e) => handleSettingChange("systemLanguage", e.target.value)}
              startContent={<FaGlobe className="text-gray-400" />}
            >
              {languages.map((lang) => (
                <SelectItem key={lang.key} value={lang.key}>
                  {lang.label}
                </SelectItem>
              ))}
            </Select>

            <Select
              label="Timezone"
              selectedKeys={[settings.timezone]}
              onChange={(e) => handleSettingChange("timezone", e.target.value)}
              startContent={<FaGlobe className="text-gray-400" />}
            >
              {timezones.map((tz) => (
                <SelectItem key={tz.key} value={tz.key}>
                  {tz.label}
                </SelectItem>
              ))}
            </Select>

            <div className="grid grid-cols-2 gap-4">
              <Select
                label="Date Format"
                selectedKeys={[settings.dateFormat]}
                onChange={(e) => handleSettingChange("dateFormat", e.target.value)}
              >
                {dateFormats.map((format) => (
                  <SelectItem key={format.key} value={format.key}>
                    {format.label}
                  </SelectItem>
                ))}
              </Select>

              <Select
                label="Currency"
                selectedKeys={[settings.currency]}
                onChange={(e) => handleSettingChange("currency", e.target.value)}
              >
                {currencies.map((currency) => (
                  <SelectItem key={currency.key} value={currency.key}>
                    {currency.label}
                  </SelectItem>
                ))}
              </Select>
            </div>
          </CardBody>
        </Card>

        {/* Notification Settings */}
        <Card>
          <CardHeader>
            <div className="flex items-center gap-3">
              <FaBell className="text-yellow-500" />
              <h3 className="text-xl font-semibold">Notification Settings</h3>
            </div>
          </CardHeader>
          <CardBody className="space-y-4">
            <Switch
              isSelected={settings.emailNotifications}
              onValueChange={(value) => handleSettingChange("emailNotifications", value)}
            >
              Email Notifications
            </Switch>

            <Switch
              isSelected={settings.smsNotifications}
              onValueChange={(value) => handleSettingChange("smsNotifications", value)}
            >
              SMS Notifications
            </Switch>

            <Divider />

            <Switch
              isSelected={settings.lowStockAlerts}
              onValueChange={(value) => handleSettingChange("lowStockAlerts", value)}
            >
              Low Stock Alerts
            </Switch>

            <Switch
              isSelected={settings.expiryAlerts}
              onValueChange={(value) => handleSettingChange("expiryAlerts", value)}
            >
              Expiry Date Alerts
            </Switch>

            <Switch
              isSelected={settings.movementAlerts}
              onValueChange={(value) => handleSettingChange("movementAlerts", value)}
            >
              Movement Alerts
            </Switch>
          </CardBody>
        </Card>

        {/* Security Settings */}
        <Card>
          <CardHeader>
            <div className="flex items-center gap-3">
              <FaShield className="text-red-500" />
              <h3 className="text-xl font-semibold">Security Settings</h3>
            </div>
          </CardHeader>
          <CardBody className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <Input
                label="Session Timeout (minutes)"
                type="number"
                value={settings.sessionTimeout}
                onChange={(e) => handleSettingChange("sessionTimeout", parseInt(e.target.value))}
                startContent={<FaKey className="text-gray-400" />}
              />

              <Input
                label="Max Login Attempts"
                type="number"
                value={settings.loginAttempts}
                onChange={(e) => handleSettingChange("loginAttempts", parseInt(e.target.value))}
                startContent={<FaKey className="text-gray-400" />}
              />
            </div>

            <Switch
              isSelected={settings.requirePasswordChange}
              onValueChange={(value) => handleSettingChange("requirePasswordChange", value)}
            >
              Require Password Change Every 90 Days
            </Switch>

            <Switch
              isSelected={settings.twoFactorAuth}
              onValueChange={(value) => handleSettingChange("twoFactorAuth", value)}
            >
              Enable Two-Factor Authentication
            </Switch>
          </CardBody>
        </Card>

        {/* Inventory Settings */}
        <Card>
          <CardHeader>
            <div className="flex items-center gap-3">
              <FaDatabase className="text-green-500" />
              <h3 className="text-xl font-semibold">Inventory Settings</h3>
            </div>
          </CardHeader>
          <CardBody className="space-y-4">
            <Input
              label="Low Stock Threshold"
              type="number"
              value={settings.lowStockThreshold}
              onChange={(e) => handleSettingChange("lowStockThreshold", parseInt(e.target.value))}
              startContent={<FaDatabase className="text-gray-400" />}
            />

            <Input
              label="Expiry Warning Days"
              type="number"
              value={settings.expiryWarningDays}
              onChange={(e) => handleSettingChange("expiryWarningDays", parseInt(e.target.value))}
              startContent={<FaDatabase className="text-gray-400" />}
            />

            <Switch
              isSelected={settings.autoReorder}
              onValueChange={(value) => handleSettingChange("autoReorder", value)}
            >
              Enable Auto Reorder
            </Switch>

            <Switch
              isSelected={settings.barcodeScanning}
              onValueChange={(value) => handleSettingChange("barcodeScanning", value)}
            >
              Enable Barcode Scanning
            </Switch>
          </CardBody>
        </Card>

        {/* Display Settings */}
        <Card>
          <CardHeader>
            <div className="flex items-center gap-3">
              <FaPalette className="text-purple-500" />
              <h3 className="text-xl font-semibold">Display Settings</h3>
            </div>
          </CardHeader>
          <CardBody className="space-y-4">
            <Select
              label="Theme"
              selectedKeys={[settings.theme]}
              onChange={(e) => handleSettingChange("theme", e.target.value)}
              startContent={<FaPalette className="text-gray-400" />}
            >
              {themes.map((theme) => (
                <SelectItem key={theme.key} value={theme.key}>
                  {theme.label}
                </SelectItem>
              ))}
            </Select>

            <Input
              label="Items Per Page"
              type="number"
              value={settings.itemsPerPage}
              onChange={(e) => handleSettingChange("itemsPerPage", parseInt(e.target.value))}
              startContent={<FaPalette className="text-gray-400" />}
            />

            <Switch
              isSelected={settings.compactMode}
              onValueChange={(value) => handleSettingChange("compactMode", value)}
            >
              Compact Mode
            </Switch>

            <Switch
              isSelected={settings.showImages}
              onValueChange={(value) => handleSettingChange("showImages", value)}
            >
              Show Product Images
            </Switch>
          </CardBody>
        </Card>

        {/* Change Password */}
        <Card>
          <CardHeader>
            <div className="flex items-center gap-3">
              <FaKey className="text-blue-500" />
              <h3 className="text-xl font-semibold">Change Password</h3>
            </div>
          </CardHeader>
          <CardBody className="space-y-4">
            <Input
              label="Current Password"
              type={showPasswords.current ? "text" : "password"}
              value={passwords.currentPassword}
              onChange={(e) => handlePasswordChange("currentPassword", e.target.value)}
              startContent={<FaKey className="text-gray-400" />}
              endContent={
                <Button
                  isIconOnly
                  variant="light"
                  onPress={() => togglePasswordVisibility("current")}
                >
                  {showPasswords.current ? <FaEyeSlash /> : <FaEye />}
                </Button>
              }
            />

            <Input
              label="New Password"
              type={showPasswords.new ? "text" : "password"}
              value={passwords.newPassword}
              onChange={(e) => handlePasswordChange("newPassword", e.target.value)}
              startContent={<FaKey className="text-gray-400" />}
              endContent={
                <Button
                  isIconOnly
                  variant="light"
                  onPress={() => togglePasswordVisibility("new")}
                >
                  {showPasswords.new ? <FaEyeSlash /> : <FaEye />}
                </Button>
              }
            />

            <Input
              label="Confirm New Password"
              type={showPasswords.confirm ? "text" : "password"}
              value={passwords.confirmPassword}
              onChange={(e) => handlePasswordChange("confirmPassword", e.target.value)}
              startContent={<FaKey className="text-gray-400" />}
              endContent={
                <Button
                  isIconOnly
                  variant="light"
                  onPress={() => togglePasswordVisibility("confirm")}
                >
                  {showPasswords.confirm ? <FaEyeSlash /> : <FaEye />}
                </Button>
              }
            />

            <Button
              color="primary"
              onPress={handleChangePassword}
              isLoading={isLoading}
              className="w-full"
            >
              Change Password
            </Button>
          </CardBody>
        </Card>
      </div>
    </div>
  );
};

export default Settings; 