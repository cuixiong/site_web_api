<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;

class Invoices extends Base
{
    protected $fillable = [
        'company_name',
        'company_address',
        'tax_code',
        'invoice_type',
        'price',
        'user_id',
        'order_id',
        'title',
        'contact_person',
        'contact_detail',
        'status',
        'apply_status',
        'phone',
        'bank_name',
        'bank_account'
    ];
    const invoiceType = [
        '1' => '普通发票',
        '2' => '增值税发票',
    ];

    const invoiceTypeEn = [
        '1' => 'regular invoice',
        '2' => 'value added tax invoice',
    ];
    const applyInvoiceStatus   = 1;  //申请中
    const alreadyInvoiceStatus = 2;  //已开票

    const invoiceStatus  = [
        self::applyInvoiceStatus   => '申请中',
        self::alreadyInvoiceStatus => '已开票',
    ];
    const invoiceStatusEn  = [
        self::applyInvoiceStatus   => 'Invoice Awaiting',
        self::alreadyInvoiceStatus => 'Invoiced',
    ];
    protected $table   = 'invoices';
    protected $appends = ['invoice_type_text', 'apply_status_text', 'apply_date'];

    public function getInvoiceTypeTextAttribute()
    {
        $text = '';
        if (isset($this->attributes['invoice_type'])) {

            if (checkSiteAccessData(['lpien'])) {
                $text = self::invoiceTypeEn[$this->attributes['invoice_type']];
            } else {
                $text = self::invoiceType[$this->attributes['invoice_type']];
            }
        }

        return $text ?? '';
    }

    public function getApplyStatusTextAttribute()
    {
        $text = '';
        if (isset($this->attributes['apply_status'])) {
            if (checkSiteAccessData(['lpien'])) {
                $text = self::invoiceStatusEn[$this->attributes['apply_status']];
            }else{
                $text = self::invoiceStatus[$this->attributes['apply_status']];
            }
        }

        return $text ?? '';
    }

    public function getApplyDateAttribute()
    {
        return date("Y-m-d", $this->attributes['created_at']);
    }
}
