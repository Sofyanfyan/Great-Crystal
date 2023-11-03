<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <style>

        body{
            font-family: 'Roboto', sans-serif;
        }

        p {
            margin: 2px;
            font-size: 12px;
        }
        
        .header,
        .header1, 
        .header2 {
            width: 100%;
            height: 5%;
            /* border: 1px solid black; */
        }
        
        .header2{
            position: relative;
            
        }
        
        .invoice {
            color: rgb(95, 95, 95);
            position: absolute;
            top: 0;
        }
        
        .logo {
            color: rgb(255, 115, 0);
            top: absolute;
            bottom: 0;
        }

        .main_text {
            font-family: 'Roboto', sans-serif;
            font-size: 20px;
        }
        
        .child_text {
            font-size: 20px;
            font-weight: 
        }
        
        .address {
            font-size: 12px;
            color: grey;
            margin-top: 20px;
        }

        .student {
            font-size: 11px;
            padding: 0;
            margin: 30px,4px;
        }
        
        .head_student {
            font-size: 12px;
            margin: 0;
        }

        .date {
            width: 100%;
            bottom: 0;
        }

        .date_container {
            vertical-align: bottom;
        }
        
    </style>
</head>
<body>

        <table class="header">

            <thead>
                <th style="width: 50%;">
                </th>
                <th style="width: 20%;">
                </th>
            </thead>
            <tbody>
                <td align="left" class="header1">
                    <h2 class="invoice">Invoice</h2>
                </td>
                <td align="center" class="header2">
                <div >
                </div>
                </td>
            </tbody>

        </table>

        <table class="header">

            <thead>
                <th style="width: 50%;">
                </th>
                <th style="width: 50%;">
                </th>
            </thead>
            <tbody>
                <td align="left" class="header1">
                </td>
                <td align="center" class="header2">
                <div class="logo">
                    <h1 style="margin: 0;">GREAT CRYSTAL</h1>
                    <h3  style="margin: 0;">SCHOOL AND COURSE CENTER</h3>
                </div>
                </td>
            </tbody>

        </table>

        
        

        <table style="width: 100%;">
            <thead>
                <th >
                </th>
                <th style="width: 30%;">
                </th>
            </thead>
            <td>
                <div class="student">
                    <p class="head_student"><strong>BILL TO</strong></p>
                    <p>Student name</p>
                    <p>{{$data->student->name}}</p>
                    <p>{{$data->student->place_birth}}</p>
                    <p>{{$data->student->nationality}}</p>
                </div>
        
            </td>
            <td class="date_container">
                <table class="date">
                    <thead>
                        <th></th>
                        <th></th>
                    </thead>
                    <tbody class="date_detail">
                        <tr >
                            <td align="left" style="padding: 0">
    
                                <p>Invoice no :</p>
                                
                            </td>
                            <td align="right" style="padding: 0">
    
                                <p><b>#{{$data->id}}</b></p>
    
                            </td>
                        </tr>
                        <tr>
                            <td align="left" style="padding: 0">
    
                                <p>Date issue :</p>
                                
                            </td>
                            <td align="right" style="padding: 0">
    
                                <p><b>{{date('d/m/Y', strtotime($data->created_at))}}</b></p>
    
                            </td>
                        </tr>
                        <tr>
                            <td align="left" style="padding: 0">
    
                                <p>Due date :</p>
                                
                            </td>
                            <td align="right" style="padding: 0">
    
                                <p><b>{{date('d/m/Y', strtotime($data->deadline_invoice))}}</b></p>
    
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </table>


        <p class="address"><b>Great Crystal School</b>, Jl. Raya Darmo Permai III, Surabaya, Indonesia</p>


        @if ($data->type == 'Book')

        <table>
            <thead>
                <th>Description</th>
                <th>Price</th>
            </thead>

            <tbody>
                <td>{{$data->type}}</td>
            </tbody>
        </table>

            
        @endif

</body>
</html>