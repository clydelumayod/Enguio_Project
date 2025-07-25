import { NextResponse } from 'next/server';

export async function POST(request) {
  try {
    const body = await request.json();
    const { product, location } = body;
    
    console.log('Inventory KPIs API called with:', { product, location });
    
    // Call the PHP backend
    const phpUrl = 'http://localhost/Enguio_Project/backend.php';
    
    const phpResponse = await fetch(phpUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        action: 'get_inventory_kpis',
        product: product || 'All',
        location: location || 'All'
      }),
    });

    if (!phpResponse.ok) {
      const errorText = await phpResponse.text();
      console.error('PHP response error:', errorText);
      throw new Error(`PHP backend error: ${phpResponse.status} - ${errorText}`);
    }

    const result = await phpResponse.json();
    console.log('KPIs response:', result);
    
    return NextResponse.json(result);
    
  } catch (error) {
    console.error('KPIs API error:', error);
    
    // Return empty data if API fails
    return NextResponse.json({
      physicalAvailable: 0,
      softReserved: 0,
      onhandInventory: 0,
      newOrderLineQty: 0,
      returned: 0,
      returnRate: 0,
      sellRate: 0,
      outOfStock: 0
    });
  }
} 