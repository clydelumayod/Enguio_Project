"use client";

import React, { useState, useEffect } from "react";
import axios from "axios";
import { toast, ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import {
  ChevronUp,
  ChevronDown,
  Plus,
  X,
  Search,
} from "lucide-react";

function ConvenienceInventory() {
  return (
    <div className="p-8">
      <h1 className="text-2xl font-bold">Convenience Store Inventory</h1>
      <p>Convenience store inventory management system.</p>
      <div className="mt-6">
        <p>This section will contain convenience store specific inventory features.</p>
      </div>
    </div>
  );
}

export default ConvenienceInventory;
