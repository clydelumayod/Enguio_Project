import { NextResponse } from 'next/server';

export async function POST(request) {
  try {
    const body = await request.json();
    const { product, location } = body;
    
    console.log('Return Rate by Product API called with:', { product, location });
    
    // Call the PHP backend
    const phpUrl = 'http://localhost/Enguio_Project/backend.php';
    
    const phpResponse = await fetch(phpUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        action: 'get_return_rate_by_product',
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
    console.log('Return Rate by Product response:', result);
    
    return NextResponse.json(result);
    
  } catch (error) {
    console.error('Return Rate by Product API error:', error);
    
    // Return empty data if API fails
    return NextResponse.json([]);
  }
} 