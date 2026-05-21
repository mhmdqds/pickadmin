<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ItemListExport implements FromView, ShouldAutoSize, WithStyles, WithColumnWidths, WithHeadings, WithEvents
{
    use Exportable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('file-exports.item-list', [
            'data' => $this->data,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'B' => 15,
            'D' => 40,
            'I' => 40,
            'J' => 40,
            'O' => 40,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $count = $this->data['data']->count();
        $lastRow = $count + 3;

        $sheet->getStyle('A2:R2')->getFont()->setBold(true);

        $sheet->getStyle('A3:R3')->getFill()->applyFromArray([
            'fillType' => 'solid',
            'color' => ['rgb' => '9F9F9F'],
        ]);

        $sheet->setShowGridlines(false);

        $styleArray = [
            'borders' => [
                'bottom' => ['borderStyle' => 'hair', 'color' => ['argb' => 'FFFF0000']],
                'top' => ['borderStyle' => 'hair', 'color' => ['argb' => 'FFFF0000']],
                'right' => ['borderStyle' => 'hair', 'color' => ['argb' => 'FF00FF00']],
                'left' => ['borderStyle' => 'hair', 'color' => ['argb' => 'FF00FF00']],
            ],
        ];

        $sheet->getStyle('A1:C1')->applyFromArray($styleArray);

        return [
            "A1:R{$lastRow}" => [
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
        $this->data['data']->each(function ($item, $index) use ($worksheet) {

            $row = $index + 4;

            $drawing = new Drawing();
            $drawing->setName($item->name);
            $drawing->setDescription($item->name);

            $imagePath = storage_path('app/public/product/' . $item->image);

            if (!is_file($imagePath)) {
                $imagePath = public_path('/assets/admin/img/160x160/img2.jpg');
            }

            $drawing->setPath($imagePath);
            $drawing->setHeight(25);
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
                $sheet->getStyle("A1:R{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle('D2:R2')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Merge cells
                $sheet->mergeCells('A1:R1');
                $sheet->mergeCells('A2:C2');
                $sheet->mergeCells('D2:R2');

                // Row heights
                $worksheet->getRowDimension(1)->setRowHeight(50);
                $worksheet->getRowDimension(2)->setRowHeight(100);

                // Apply row height to data rows
                for ($i = 4; $i <= $highestRow; $i++) {
                    $worksheet->getRowDimension($i)->setRowHeight(30);
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
