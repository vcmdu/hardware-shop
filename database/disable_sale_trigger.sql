-- Disable the sale insert trigger since stock deduction, ledger entries,
-- and customer balance updates are now handled in PHP (Sale::createSale).
-- The trigger had a timing bug: it fires AFTER INSERT on sales but before
-- sale_items are inserted, so it found no items to process.

DROP TRIGGER IF EXISTS trg_sale_insert ON sales;
