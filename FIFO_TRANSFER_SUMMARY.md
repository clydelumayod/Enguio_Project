# FIFO Transfer Summary

## Scenario
**Product:** Generic Product  
**Transfer Quantity:** 150 units  
**Transfer Method:** First-In, First-Out (FIFO)

## Available Batches (in order of creation - oldest first)
1. **Batch 1:** 100 units at ₱55.00 each
2. **Batch 2:** 20 units at ₱55.00 each  
3. **Batch 3:** 100 units at ₱50.00 each

## FIFO Transfer Calculation

### Step-by-Step Deduction Process

**Remaining Transfer Quantity:** 150 units

1. **Batch 1 (Oldest)**
   - Available: 100 units
   - Unit Price: ₱55.00
   - Quantity to Deduct: 100 units (fully consumed)
   - Total Value: 100 × ₱55.00 = **₱5,500.00**
   - Remaining Transfer Quantity: 150 - 100 = **50 units**

2. **Batch 2 (Second Oldest)**
   - Available: 20 units
   - Unit Price: ₱55.00
   - Quantity to Deduct: 20 units (fully consumed)
   - Total Value: 20 × ₱55.00 = **₱1,100.00**
   - Remaining Transfer Quantity: 50 - 20 = **30 units**

3. **Batch 3 (Newest)**
   - Available: 100 units
   - Unit Price: ₱50.00
   - Quantity to Deduct: 30 units (partial consumption)
   - Total Value: 30 × ₱50.00 = **₱1,500.00**
   - Remaining Transfer Quantity: 30 - 30 = **0 units**

## Transfer Summary

| Batch Reference | Quantity Deducted | Unit Price | Total Value |
|-----------------|-------------------|------------|-------------|
| Batch 1         | 100 units         | ₱55.00     | ₱5,500.00   |
| Batch 2         | 20 units          | ₱55.00     | ₱1,100.00   |
| Batch 3         | 30 units          | ₱50.00     | ₱1,500.00   |
| **TOTAL**       | **150 units**     | -          | **₱8,100.00** |

## Final Result
- **Total Transfer Quantity:** 150 units
- **Total Transfer Value:** ₱8,100.00
- **Average Unit Cost:** ₱54.00 (₱8,100.00 ÷ 150 units)

## Remaining Stock After Transfer
- **Batch 1:** 0 units (fully consumed)
- **Batch 2:** 0 units (fully consumed)  
- **Batch 3:** 70 units remaining (100 - 30)

## Notes
- The FIFO principle ensures that the oldest inventory is consumed first
- This method provides accurate cost tracking and inventory valuation
- The transfer successfully consumed 150 units across three batches
- The average unit cost of ₱54.00 reflects the weighted average of the consumed batches 