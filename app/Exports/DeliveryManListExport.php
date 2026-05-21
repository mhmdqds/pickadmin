<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DeliveryManListExport implements FromView, ShouldAutoSize, WithStyles, WithColumnWidths, WithHeadings, WithEvents, WithColumnFormatting
{
    use Exportable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('file-exports.deliveryman-list', [
            'data' => $this->data,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'E' => 45,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $count = $this->data['delivery_men']->count();
        $lastRow = $count + 4;

        $sheet->getStyle('A2:N4')->getFont()->setBold(true);

        $sheet->getStyle('A4:N4')->getFont()
            ->setBold(true)
            ->getColor()
            ->setARGB('FFFFFF');

        $sheet->getStyle('A4:N4')->getFill()->applyFromArray([
            'fillType' => 'solid',
            'color' => ['rgb' => '005D5F'],
        ]);

        $sheet->getStyle("G5:K{$lastRow}")
            ->getFill()
            ->applyFromArray([
                'fillType' => 'solid',
                'color' => ['rgb' => 'FFE599'],
            ]);

        $sheet->setShowGridlines(false);

        return [
            "A1:N{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    public function setImage($worksheet)
    {
        $this->data['delivery_men']->each(function ($item, $index) use ($worksheet) {

            $row = $index + 5;

            $drawing = new Drawing();
            $drawing->setName($item->f_name);
            $drawing->setDescription($item->f_name);

            $imagePath = storage_path('app/public/delivery-man/' . $item->image);

            if (!is_file($imagePath)) {
                $imagePath = public_path('/assets/admin/img/160x160/img2.jpg');
            }

            $drawing->setPath($imagePath);
            $drawing->setHeight(20);
            $drawing->setCoordinates("B{$row}");
            $drawing->setWorksheet($worksheet);

            $drawing->setResizeProportional(true);
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(3);
        });
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet;
                $worksheet = $sheet->getDelegate();

                $highestRow = $worksheet->getHighestRow();

                // Alignment
                $sheet->getStyle("A1:N{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle('D2:N3')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Merge cells
                $sheet->mergeCells('A1:N1');
                $sheet->mergeCells('A2:C2');
                $sheet->mergeCells('D2:N2');
                $sheet->mergeCells('A3:C3');
                $sheet->mergeCells('D3:N3');

                // Row heights
                $worksheet->getRowDimension(1)->setRowHeight(50);
                $worksheet->getRowDimension(2)->setRowHeight(100);
                $worksheet->getRowDimension(3)->setRowHeight(80);

                for ($i = 5; $i <= $highestRow; $i++) {
                    $worksheet->getRowDimension($i)->setRowHeight(40);
                }

                // Insert images
                $this->setImage($worksheet);
            },
        ];
    }

    public function headings(): array
    {
        return [
            '1'
        ];
    }
}
