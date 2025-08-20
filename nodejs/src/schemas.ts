/**
 * Zod validation schemas for MCP tool inputs
 * Author: Meshack Musyoka
 */

import { z } from 'zod';

// Phone number validation for Kenyan numbers
const phoneSchema = z.string().regex(
  /^(?:254|\+254|0)?([17]\d{8})$/,
  'Invalid phone number format. Use format: 254XXXXXXXX or 07XXXXXXXX'
);

// Amount validation
const amountSchema = z.number().positive('Amount must be positive').max(70000, 'Amount exceeds limit');

// URL validation
const urlSchema = z.string().url('Invalid URL format');

// Generate Token Schema
export const generateTokenSchema = z.object({});

// STK Push Schema
export const stkPushSchema = z.object({
  amount: amountSchema,
  phone_number: phoneSchema,
  callback_url: urlSchema,
  account_reference: z.string().min(1).max(12, 'Account reference must be 1-12 characters'),
  transaction_desc: z.string().min(1).max(13, 'Transaction description must be 1-13 characters')
});

// STK Query Schema
export const stkQuerySchema = z.object({
  checkout_request_id: z.string().min(1, 'Checkout request ID is required')
});

// C2B Register Schema
export const c2bRegisterSchema = z.object({
  confirmation_url: urlSchema,
  validation_url: urlSchema,
  response_type: z.enum(['Cancelled', 'Completed']).optional().default('Completed')
});

// C2B Simulate Schema
export const c2bSimulateSchema = z.object({
  amount: amountSchema,
  msisdn: phoneSchema,
  command_id: z.enum(['CustomerPayBillOnline', 'CustomerBuyGoodsOnline']).optional().default('CustomerPayBillOnline'),
  bill_ref_number: z.string().optional()
});

// B2C Payment Schema
export const b2cPaymentSchema = z.object({
  amount: amountSchema,
  party_b: phoneSchema,
  command_id: z.enum(['SalaryPayment', 'BusinessPayment', 'PromotionPayment']).optional().default('BusinessPayment'),
  remarks: z.string().min(1).max(100, 'Remarks must be 1-100 characters'),
  queue_timeout_url: urlSchema,
  result_url: urlSchema,
  occasion: z.string().max(100, 'Occasion must be maximum 100 characters').optional()
});

// B2B Payment Schema
export const b2bPaymentSchema = z.object({
  amount: amountSchema,
  party_b: z.string().min(1, 'Party B is required'),
  command_id: z.enum(['BusinessPayBill', 'BusinessBuyGoods', 'DisburseFundsToBusiness', 'BusinessToBusinessTransfer']).optional().default('BusinessPayBill'),
  remarks: z.string().min(1).max(100, 'Remarks must be 1-100 characters'),
  queue_timeout_url: urlSchema,
  result_url: urlSchema,
  account_reference: z.string().min(1).max(12, 'Account reference must be 1-12 characters')
});

// Account Balance Schema
export const accountBalanceSchema = z.object({
  identifier_type: z.enum(['1', '2', '4']).optional().default('4'),
  remarks: z.string().min(1).max(100, 'Remarks must be 1-100 characters'),
  queue_timeout_url: urlSchema,
  result_url: urlSchema
});

// Transaction Status Schema
export const transactionStatusSchema = z.object({
  transaction_id: z.string().min(1, 'Transaction ID is required'),
  identifier_type: z.enum(['1', '2', '4']).optional().default('4'),
  result_url: urlSchema,
  queue_timeout_url: urlSchema,
  remarks: z.string().min(1).max(100, 'Remarks must be 1-100 characters'),
  occasion: z.string().max(100, 'Occasion must be maximum 100 characters').optional()
});

// Reversal Schema
export const reversalSchema = z.object({
  transaction_id: z.string().min(1, 'Transaction ID is required'),
  amount: amountSchema,
  receiver_party: z.string().min(1, 'Receiver party is required'),
  receiver_identifier_type: z.enum(['1', '2', '4', '11']).optional().default('11'),
  result_url: urlSchema,
  queue_timeout_url: urlSchema,
  remarks: z.string().min(1).max(100, 'Remarks must be 1-100 characters'),
  occasion: z.string().max(100, 'Occasion must be maximum 100 characters').optional()
});

// Generate QR Schema
export const generateQRSchema = z.object({
  merchant_name: z.string().min(1).max(22, 'Merchant name must be 1-22 characters'),
  ref_no: z.string().min(1).max(12, 'Reference number must be 1-12 characters'),
  amount: amountSchema,
  trx_code: z.enum(['BG', 'WA', 'PB', 'SM']),
  cpi: z.string().min(1, 'CPI is required'),
  size: z.enum(['300']).optional().default('300')
});

export type GenerateTokenInput = z.infer<typeof generateTokenSchema>;
export type STKPushInput = z.infer<typeof stkPushSchema>;
export type STKQueryInput = z.infer<typeof stkQuerySchema>;
export type C2BRegisterInput = z.infer<typeof c2bRegisterSchema>;
export type C2BSimulateInput = z.infer<typeof c2bSimulateSchema>;
export type B2CPaymentInput = z.infer<typeof b2cPaymentSchema>;
export type B2BPaymentInput = z.infer<typeof b2bPaymentSchema>;
export type AccountBalanceInput = z.infer<typeof accountBalanceSchema>;
export type TransactionStatusInput = z.infer<typeof transactionStatusSchema>;
export type ReversalInput = z.infer<typeof reversalSchema>;
export type GenerateQRInput = z.infer<typeof generateQRSchema>;