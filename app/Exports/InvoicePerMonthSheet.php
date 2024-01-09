<?php

namespace App\Exports;

use App\Models\Bill;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoicePerMonthSheet implements WithTitle, WithHeadings, ShouldAutoSize, FromArray, WithStyles
{
    private $array, $month, $year, $map_student, $map_grade;

    public function __construct(array $array, int $year, $month, array $map_student, array $map_grade)
    {
        $this->array = $array;
        $this->month = $month;
        $this->year  = $year;
        $this->map_student = $map_student;
        $this->map_grade = $map_grade;
    }

    /**
     * @return Builder
     */
    public function array(): array
    {
        return $this->array;
    }                                                

    public function headings(): array
    {

        if(sizeof($this->array)==0){
            
            return ['empty data'];
        }


        if($this->month === 'Capital Fee') {
            return [
                'No Invoice',
                'Grades',
                'Student name',
                'Type',
                'Installment/Month',
                'Date created',
                'Date past due',
                'Total',
                'Done Payment',
                'Charge',
                'Amount',
                'Paid date',
                'Status',
            ];
        }


        return [
            'No Invoice',
            'Grades',
            'Student name',
            'Type',
            'Date created',
            'Date past due',
            'Charge',
            'Amount',
            'Paid date',
            'Status',
        ];

    }

    public function styles(Worksheet $sheet)
    {

        if(sizeof($this->array)==0){
            
            return [
                1    => ['font' => ['bold' => true]],
            ];

        } else if($this->month == 'Capital Fee') {

            $max = 'A1:M'.sizeof($this->array)+1;

            //font size
            
        } else {
            
            $max = 'A1:J'.sizeof($this->array)+1;
        }

        $sheet->getStyle($max)->getFont()->setSize(12);
        //merge student
        foreach($this->map_student as $student) {
            
            $sheet->mergeCells('C'.$student[0].':'.'C'.$student[1]);
            $sheet->mergeCells('E'.$student[0].':'.'E'.$student[1]);
            $sheet->mergeCells('F'.$student[0].':'.'F'.$student[1]);
            $sheet->mergeCells('H'.$student[0].':'.'H'.$student[1]);
            $sheet->mergeCells('I'.$student[0].':'.'I'.$student[1]);
        }

        //merge grade 
        foreach($this->map_grade as $grade) {
            
            $sheet->mergeCells('B'.$grade[0].':'.'B'.$grade[1]);
        }
        
        //styles
        $styleArray = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                    'color' => ['argb' => '00000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];
        
        $sheet->getStyle($max)->applyFromArray($styleArray);

        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],
            'B'  => ['font' => ['bold' => true ]],
            'M'  => ['font' => ['bold' => true ]],
        ];

    }

    public function title(): string
    {
        if(is_numeric($this->month)) {
            $date = Carbon::create($this->year, $this->month);
            return "Monthly Fee " . date("F Y", strtotime($date));
        } else {
            return $this->month;
        }
    }

    /**
     * @return string
     */
}
