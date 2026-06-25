<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Sale;
use App\Models\Customer;
use App\Helpers\AuditLogger;
use App\Core\Session;
use TCPDF;

class SalesController extends Controller
{
  public function index(Request $request, Response $response)
  {
    $this->requireAuth();
    $this->render('sales/index', ['title' => 'Sales History']);
  }

  public function pos(Request $request, Response $response)
  {
    $this->requireAuth();
    $custModel = new Customer();
    $customers = $custModel->fetchAll("SELECT id, name, customer_code, outstanding_balance, credit_limit FROM customers ORDER BY name");
    $this->render('sales/pos', ['title' => 'POS - Point of Sale', 'customers' => $customers], 'main');
  }

  public function apiList(Request $request, Response $response)
  {
    $this->requireAuth();
    $model = new Sale();
    $response->json(['success' => true, 'data' => $model->allWithCustomer()]);
  }

  public function apiCreate(Request $request, Response $response)
  {
    $this->requireAuth();
    $this->validateCsrf($request);
    $body = $request->getBody();

    if (empty($body['items']) || !isset($body['customer_id']) || $body['customer_id'] === '' || $body['customer_id'] === null) {
      $response->json(['success' => false, 'message' => 'Customer and items are required.'], 400);
    }

    $model = new Sale();
    $count = $model->query("SELECT COUNT(*) FROM sales")->fetchColumn();

    // Get invoice prefix from settings
    $settingRow = $model->query("SELECT value FROM settings WHERE key = 'invoice_prefix'")->fetch();
    $prefix = $settingRow ? $settingRow['value'] : 'INV-';
    $invoiceNumber = $prefix . date('Ymd') . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);

    $items = is_string($body['items']) ? json_decode($body['items'], true) : $body['items'];
    $gstTotal = 0;
    $grandTotal = 0;
    foreach ($items as &$item) {
      $lineTotal = $item['quantity'] * $item['price'];
      $gstAmt = $lineTotal * ($item['gst_percentage'] / 100);
      $item['total'] = $lineTotal + $gstAmt - ($item['discount'] ?? 0);
      $gstTotal += $gstAmt;
      $grandTotal += $item['total'];
    }

    $discount = (float) ($body['discount'] ?? 0);
    $paidAmount = (float) ($body['paid_amount'] ?? $grandTotal - $discount);
    $paymentStatus = $paidAmount >= ($grandTotal - $discount) ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

    try {
      $saleId = $model->createSale([
        'invoice_number' => $invoiceNumber,
        'customer_id' => (int) $body['customer_id'],
        'date' => $body['date'] ?? date('Y-m-d'),
        'discount' => $discount,
        'gst_total' => $gstTotal,
        'grand_total' => $grandTotal - $discount,
        'payment_method' => $body['payment_method'] ?? 'cash',
        'payment_status' => $paymentStatus,
        'paid_amount' => $paidAmount
      ], $items);
    } catch (\Exception $e) {
      $response->json(['success' => false, 'message' => 'Sale failed: ' . $e->getMessage()], 500);
    }

    AuditLogger::log('Sale created', ['invoice' => $invoiceNumber, 'total' => $grandTotal - $discount]);
    $response->json(['success' => true, 'message' => 'Sale completed! Invoice #' . $invoiceNumber, 'sale_id' => $saleId, 'invoice_number' => $invoiceNumber]);
  }

  public function pdf(Request $request, Response $response, array $params)
  {
    $this->requireAuth();
    $id = (int) ($params['id'] ?? 0);
    $model = new Sale();
    $settingModel = new \App\Models\Setting();
    $settings = $settingModel->getAll();
    $sale = $model->findWithCustomer($id);
    $items = $model->getItems($id);
    if (!$sale) {
      http_response_code(404);
      die('Not found.');
    }

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetTitle('Invoice #' . $sale['invoice_number']);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->AddPage();

    $shopName = htmlspecialchars($settings['shop_name'] ?? 'Hardware Shop');
    $shopAddr = htmlspecialchars($settings['shop_address'] ?? '');
    $shopGst = htmlspecialchars($settings['shop_gst'] ?? '');
    $currency = htmlspecialchars($settings['currency_symbol'] ?? '₹');

    $html = "
        <table><tr>
          <td><h2>$shopName</h2><small>$shopAddr</small><br><small>GST: $shopGst</small></td>
          <td align='right'><h3>TAX INVOICE</h3>
            <b>Invoice #:</b> {$sale['invoice_number']}<br>
            <b>Date:</b> {$sale['date']}<br>
            <b>Payment:</b> {$sale['payment_method']} ({$sale['payment_status']})
          </td>
        </tr></table>
        <hr/>
        <b>Bill To:</b><br>
        {$sale['customer_name']}<br>
        {$sale['mobile']}<br>
        GST: {$sale['gst_number']}
        <br><br>
        <table border='1' cellpadding='4'>
          <tr style='background:#003566;color:#fff;'>
            <th>#</th><th>Product</th><th>Unit</th><th>Qty</th><th>Price</th><th>GST%</th><th>Disc</th><th>Total</th>
          </tr>";
    foreach ($items as $i => $item) {
      $html .= "<tr>
              <td>" . ($i + 1) . "</td>
              <td>{$item['product_name']}</td>
              <td>{$item['unit']}</td>
              <td>{$item['quantity']}</td>
              <td>{$currency}{$item['price']}</td>
              <td>{$item['gst_percentage']}%</td>
              <td>{$currency}{$item['discount']}</td>
              <td>{$currency}{$item['total']}</td>
            </tr>";
    }
    $html .= "</table>
        <br>
        <table><tr>
          <td width='60%'><i>Amount in words: <b>" . self::numberToWords((float) $sale['grand_total']) . " Only</b></i></td>
          <td align='right'>
            GST Total: <b>{$currency}{$sale['gst_total']}</b><br>
            Discount: <b>{$currency}{$sale['discount']}</b><br>
            <h3>Grand Total: {$currency}{$sale['grand_total']}</h3>
            Paid: {$currency}{$sale['paid_amount']}
          </td>
        </tr></table>";

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('Invoice_' . $sale['invoice_number'] . '.pdf', 'I');
    exit;
  }

  private static function numberToWords(float $num): string
  {
    $ones = [
      '',
      'One',
      'Two',
      'Three',
      'Four',
      'Five',
      'Six',
      'Seven',
      'Eight',
      'Nine',
      'Ten',
      'Eleven',
      'Twelve',
      'Thirteen',
      'Fourteen',
      'Fifteen',
      'Sixteen',
      'Seventeen',
      'Eighteen',
      'Nineteen'
    ];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    $n = (int) $num;
    if ($n < 20)
      return $ones[$n];
    if ($n < 100)
      return $tens[(int) ($n / 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
    if ($n < 1000)
      return $ones[(int) ($n / 100)] . ' Hundred' . ($n % 100 ? ' ' . self::numberToWords($n % 100) : '');
    if ($n < 100000)
      return self::numberToWords((int) ($n / 1000)) . ' Thousand' . ($n % 1000 ? ' ' . self::numberToWords($n % 1000) : '');
    return 'Rupees ' . self::numberToWords((int) ($n / 100000)) . ' Lakh' . ($n % 100000 ? ' ' . self::numberToWords($n % 100000) : '');
  }
}
