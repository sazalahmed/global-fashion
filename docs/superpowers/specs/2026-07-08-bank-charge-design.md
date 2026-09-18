# Bank Charge on Balance Transfers (Group F)

**Date:** 2026-07-08
**Module:** `Modules/Payment` (+ Accounting account seed)
**Status:** Approved

## Decision
Bank/mobile-banking fees are captured as a **Bank Charge** input on balance
transfers. The transfer itself is balance-neutral (cash/bank → cash/bank); the
charge is the only real expense.

## Schema (migration)
- Add `charge` decimal(15,2) default 0 and `journal_entry_id` nullable to `balance_transfers`.
- Seed expense account `5160 — Bank Charges` if absent.

## Model
`BalanceTransfer`: add `charge`, `journal_entry_id` to fillable; cast charge decimal:2.

## storeTransfer (PaymentAccountController)
- Validate `charge` nullable numeric min:0.
- Create the transfer with the charge.
- If charge > 0, post JE: DR Bank Charges (5160) / CR the source account's
  cash/bank ledger (type→code: cash 1001, mobile_banking 1002, bank/card 1004);
  store `journal_entry_id`.

## Ledger balance
- In the account ledger, transfers-out `out_amount` becomes `amount + charge`
  so the source account balance drops by the transfer plus its charge.

## View (accounts/transfers)
- Add a "Bank Charge" input to the transfer form (default 0).
- Show a Charge column in the transfers table.

## Verification
- Transfer 10000 with charge 20 from a bank account → source ledger out = 10020;
  destination in = 10000; JE DR 5160 20 / CR bank 20 (balanced).
- Charge 0 → no JE, behaves as before.
