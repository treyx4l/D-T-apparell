-- Migration: product quantity, sold-out rules, storage bucket policies
-- Run in Supabase SQL Editor if you already applied the base schema.

-- =============================================================================
-- PRODUCTS: quantity & sold_out
-- =============================================================================
ALTER TABLE public.products
  ADD COLUMN IF NOT EXISTS quantity INTEGER NOT NULL DEFAULT 0 CHECK (quantity >= 0),
  ADD COLUMN IF NOT EXISTS sold_out BOOLEAN NOT NULL DEFAULT false;

CREATE INDEX IF NOT EXISTS products_sold_out_idx ON public.products(sold_out);

-- Replace delete policy: admins may only delete sold-out products
DROP POLICY IF EXISTS "Admins can delete products" ON public.products;
CREATE POLICY "Admins can delete sold out products"
  ON public.products FOR DELETE
  USING (
    sold_out = true
    AND EXISTS (
      SELECT 1 FROM public.profiles
      WHERE id = auth.uid() AND role = 'admin'
    )
  );

-- Auto-mark sold out when quantity reaches zero
CREATE OR REPLACE FUNCTION public.sync_product_sold_out()
RETURNS TRIGGER AS $$
BEGIN
  IF NEW.quantity <= 0 THEN
    NEW.quantity := 0;
    NEW.sold_out := true;
  ELSE
    NEW.sold_out := false;
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS products_sync_sold_out ON public.products;
CREATE TRIGGER products_sync_sold_out
  BEFORE INSERT OR UPDATE OF quantity ON public.products
  FOR EACH ROW EXECUTE FUNCTION public.sync_product_sold_out();

-- Decrement stock when an order is placed
CREATE OR REPLACE FUNCTION public.decrement_stock_on_order()
RETURNS TRIGGER AS $$
DECLARE
  item JSONB;
  pid UUID;
  qty INTEGER;
BEGIN
  FOR item IN SELECT * FROM jsonb_array_elements(NEW.items)
  LOOP
    pid := (item->>'id')::UUID;
    qty := GREATEST(1, COALESCE((item->>'quantity')::INTEGER, 1));
    UPDATE public.products
    SET quantity = GREATEST(0, quantity - qty)
    WHERE id = pid;
  END LOOP;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS orders_decrement_stock ON public.orders;
CREATE TRIGGER orders_decrement_stock
  AFTER INSERT ON public.orders
  FOR EACH ROW EXECUTE FUNCTION public.decrement_stock_on_order();

-- =============================================================================
-- STORAGE: product images bucket
-- Create bucket in Dashboard or uncomment below (public read for product images)
-- =============================================================================
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES (
  'product-images',
  'product-images',
  true,
  5242880,
  ARRAY['image/jpeg', 'image/png', 'image/webp', 'image/gif']
)
ON CONFLICT (id) DO UPDATE SET public = true;

DROP POLICY IF EXISTS "Public read product images" ON storage.objects;
CREATE POLICY "Public read product images"
  ON storage.objects FOR SELECT
  USING (bucket_id = 'product-images');

DROP POLICY IF EXISTS "Admins upload product images" ON storage.objects;
CREATE POLICY "Admins upload product images"
  ON storage.objects FOR INSERT
  WITH CHECK (
    bucket_id = 'product-images'
    AND EXISTS (
      SELECT 1 FROM public.profiles
      WHERE id = auth.uid() AND role = 'admin'
    )
  );

DROP POLICY IF EXISTS "Admins update product images" ON storage.objects;
CREATE POLICY "Admins update product images"
  ON storage.objects FOR UPDATE
  USING (
    bucket_id = 'product-images'
    AND EXISTS (
      SELECT 1 FROM public.profiles
      WHERE id = auth.uid() AND role = 'admin'
    )
  );

DROP POLICY IF EXISTS "Admins delete product images" ON storage.objects;
CREATE POLICY "Admins delete product images"
  ON storage.objects FOR DELETE
  USING (
    bucket_id = 'product-images'
    AND EXISTS (
      SELECT 1 FROM public.profiles
      WHERE id = auth.uid() AND role = 'admin'
    )
  );
