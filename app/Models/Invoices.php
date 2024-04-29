<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;

class Invoices extends Base {
    const invoiceType
        = [
            '1' => '普通发票',
            '2' => '增值税发票',
        ];
    const invoiceStatus
        = [
            '0' => '待开票',
            '1' => '已票中',
        ];
    protected $table   = 'invoices';
    protected $appends = ['invoice_type_text', 'apply_status_text', 'apply_date'];

    public function getInvoiceTypeTextAttribute() {
        $text = '';
        if (isset($this->attributes['invoice_type'])) {
            $text = self::invoiceType[$this->attributes['invoice_type']];
        }

        return $text ?? '';
    }

    public function getApplyStatusTextAttribute() {
        $text = '';
        if (isset($this->attributes['apply_status'])) {
            $text = self::invoiceStatus[$this->attributes['apply_status']];
        }

        return $text ?? '';
    }

    public function getApplyDateAttribute() {
        return date("Y-m-d", $this->attributes['created_at']);
    }
}
