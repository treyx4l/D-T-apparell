-- Fix product image URLs missing /public/ in Supabase Storage path
-- Run in Supabase SQL Editor if images return HTTP 400

UPDATE public.products
SET image = regexp_replace(
  image,
  '/storage/v1/object/(?!public/)([^/]+)/(.+)$',
  '/storage/v1/object/public/\1/\2'
)
WHERE image LIKE '%/storage/v1/object/%'
  AND image NOT LIKE '%/storage/v1/object/public/%';
