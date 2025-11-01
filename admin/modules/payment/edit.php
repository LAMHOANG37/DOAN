<?php

include '../../config.php';

// fetch payment data
$id = $_GET['id'];

$sql ="Select * from payment where id = '$id'";
$re = mysqli_query($conn,$sql);
while($row=mysqli_fetch_array($re))
{
    $Name = $row['Name'];
    $Email = $row['Email'];
    $RoomType = $row['RoomType'];
    $Bed = $row['Bed'];
    $NoofRoom = $row['NoofRoom'];
    $Meal = $row['meal'];
    $cin = $row['cin'];
    $cout = $row['cout'];
    $noofdays = $row['noofdays'];
    $roomtotal = $row['roomtotal'];
    $bedtotal = $row['bedtotal'];
    $mealtotal = $row['mealtotal'];
    $finaltotal = $row['finaltotal'];
}

if (isset($_POST['paymentedit'])) {
    $EditName = $_POST['Name'];
    $EditEmail = $_POST['Email'];
    $EditRoomType = $_POST['RoomType'];
    $EditBed = $_POST['Bed'];
    $EditNoofRoom = $_POST['NoofRoom'];
    $EditMeal = $_POST['Meal'];
    $Editcin = $_POST['cin'];
    $Editcout = $_POST['cout'];

    // Calculate new prices
    $type_of_room = 0;
    if($EditRoomType=="Superior Room")
    {
        $type_of_room = 3000;
    }
    else if($EditRoomType=="Deluxe Room")
    {
        $type_of_room = 2000;
    }
    else if($EditRoomType=="Guest House")
    {
        $type_of_room = 1500;
    }
    else if($EditRoomType=="Single Room")
    {
        $type_of_room = 1000;
    }
    
    
    if($EditBed=="Single")
    {
        $type_of_bed = $type_of_room * 1/100;
    }
    else if($EditBed=="Double")
    {
        $type_of_bed = $type_of_room * 2/100;
    }
    else if($EditBed=="Triple")
    {
        $type_of_bed = $type_of_room * 3/100;
    }
    else if($EditBed=="Quad")
    {
        $type_of_bed = $type_of_room * 4/100;
    }
    else if($EditBed=="None")
    {
        $type_of_bed = $type_of_room * 0/100;
    }

    if($EditMeal=="Room only")
    {
        $type_of_meal=$type_of_bed * 0;
    }
    else if($EditMeal=="Breakfast")
    {
        $type_of_meal=$type_of_bed * 2;
    }
    else if($EditMeal=="Half Board")
    {
        $type_of_meal=$type_of_bed * 3;
    }
    else if($EditMeal=="Full Board")
    {
        $type_of_meal=$type_of_bed * 4;
    }

    // Calculate date difference
    $date1 = new DateTime($Editcin);
    $date2 = new DateTime($Editcout);
    $Editnoofdays = $date2->diff($date1)->days;

    $editttot = $type_of_room * $Editnoofdays * $EditNoofRoom;
    $editmepr = $type_of_meal * $Editnoofdays;
    $editbtot = $type_of_bed * $Editnoofdays;

    $editfintot = $editttot + $editmepr + $editbtot;

    $sql = "UPDATE payment SET Name = '$EditName', Email = '$EditEmail', RoomType='$EditRoomType', Bed='$EditBed', NoofRoom='$EditNoofRoom', meal='$EditMeal', cin='$Editcin', cout='$Editcout', noofdays = '$Editnoofdays', roomtotal = '$editttot', bedtotal = '$editbtot', mealtotal = '$editmepr', finaltotal = '$editfintot' WHERE id = '$id'";

    $result = mysqli_query($conn,$sql);

    if ($result) {
        echo "<script>alert('Payment updated successfully!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Error updating payment!');</script>";
    }

}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- boot -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <!-- fontowesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- sweet alert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <link rel="stylesheet" href="../booking/booking.css">
    <style>
        #editpanel{
            position : fixed;
            z-index: 1000;
            height: 100%;
            width: 100%;
            display: flex;
            justify-content: center;
            background-color: #00000079;
        }
        #editpanel .paymentdetailpanelform{
            height: 620px;
            width: 1170px;
            background-color: #ccdff4;
            border-radius: 10px;  
            position: relative;
            top: 20px;
            animation: guestinfoform .3s ease;
        }
        .paymentdetailpanelform .head{
            padding: 20px;
            background-color: #007bff;
            color: white;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .paymentdetailpanelform .head i{
            font-size: 24px;
            cursor: pointer;
            color: white;
        }
        .paymentdetailpanelform .middle{
            display: flex;
            padding: 30px;
            justify-content: space-between;
        }
        .paymentdetailpanelform .middle .guestinfo,
        .paymentdetailpanelform .middle .reservationinfo{
            width: 48%;
        }
        .paymentdetailpanelform .middle h4{
            margin-bottom: 20px;
            color: #007bff;
        }
        .paymentdetailpanelform .middle input,
        .paymentdetailpanelform .middle select{
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .paymentdetailpanelform .middle .line{
            width: 2px;
            background-color: #007bff;
        }
        .paymentdetailpanelform .footer{
            padding: 20px;
            text-align: center;
        }
        .datesection{
            display: flex;
            justify-content: space-between;
        }
        .datesection span{
            width: 48%;
        }
        .price-info{
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .price-info p{
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
        }
        .price-info .total{
            font-weight: bold;
            font-size: 18px;
            color: #007bff;
            border-top: 2px solid #007bff;
            padding-top: 10px;
            margin-top: 10px;
        }

    </style>
    <title>Edit Payment - BlueBird</title>
</head>
<body>
    <div id="editpanel">
        <form method="POST" class="paymentdetailpanelform">
            <div class="head">
                <h3>EDIT PAYMENT</h3>
                <a href="./index.php"><i class="fa-solid fa-circle-xmark"></i></a>
            </div>
            <div class="middle">
                <div class="guestinfo">
                    <h4>Guest Information</h4>
                    <input type="text" name="Name" placeholder="Enter Full name" value="<?php echo $Name ?>" required>
                    <input type="email" name="Email" placeholder="Enter Email" value="<?php echo $Email ?>" required>
                    
                    <h4 style="margin-top: 20px;">Current Payment Details</h4>
                    <div class="price-info">
                        <p><span>Room Total:</span> <span>₫<?php echo number_format($roomtotal, 0, ',', '.') ?></span></p>
                        <p><span>Bed Total:</span> <span>₫<?php echo number_format($bedtotal, 0, ',', '.') ?></span></p>
                        <p><span>Meal Total:</span> <span>₫<?php echo number_format($mealtotal, 0, ',', '.') ?></span></p>
                        <p class="total"><span>Final Total:</span> <span>₫<?php echo number_format($finaltotal, 0, ',', '.') ?></span></p>
                    </div>
                </div>

                <div class="line"></div>

                <div class="reservationinfo">
                    <h4>Reservation Information</h4>
                    <select name="RoomType" class="selectinput" required>
                        <option value="">Type Of Room</option>
                        <option value="Phòng Cao Cấp" <?php if($RoomType=="Phòng Cao Cấp") echo "selected"; ?>>PHÒNG CAO CẤP (₫3.000.000/đêm)</option>
                        <option value="Phòng Sang Trọng" <?php if($RoomType=="Phòng Sang Trọng") echo "selected"; ?>>PHÒNG SANG TRỌNG (₫2.000.000/đêm)</option>
                        <option value="Nhà Khách" <?php if($RoomType=="Nhà Khách") echo "selected"; ?>>NHÀ KHÁCH (₫1.500.000/đêm)</option>
                        <option value="Phòng Đơn" <?php if($RoomType=="Phòng Đơn") echo "selected"; ?>>PHÒNG ĐƠN (₫1.000.000/đêm)</option>
                    </select>
                    <select name="Bed" class="selectinput" required>
                        <option value="">Loại Giường</option>
                        <option value="Đơn" <?php if($Bed=="Đơn") echo "selected"; ?>>Đơn</option>
                        <option value="Đôi" <?php if($Bed=="Đôi") echo "selected"; ?>>Đôi</option>
                        <option value="Ba" <?php if($Bed=="Ba") echo "selected"; ?>>Ba</option>
                        <option value="Bốn" <?php if($Bed=="Bốn") echo "selected"; ?>>Bốn</option>
                        <option value="Không" <?php if($Bed=="Không") echo "selected"; ?>>Không</option>
                    </select>
                    <select name="NoofRoom" class="selectinput" required>
                        <option value="">No of Room</option>
                        <option value="1" <?php if($NoofRoom=="1") echo "selected"; ?>>1</option>
                        <option value="2" <?php if($NoofRoom=="2") echo "selected"; ?>>2</option>
                        <option value="3" <?php if($NoofRoom=="3") echo "selected"; ?>>3</option>
                    </select>
                    <select name="Meal" class="selectinput" required>
                        <option value="">Bữa Ăn</option>
                        <option value="Chỉ phòng" <?php if($Meal=="Chỉ phòng") echo "selected"; ?>>Chỉ phòng</option>
                        <option value="Bữa sáng" <?php if($Meal=="Bữa sáng") echo "selected"; ?>>Bữa sáng</option>
                        <option value="Nửa suất" <?php if($Meal=="Nửa suất") echo "selected"; ?>>Nửa suất</option>
                        <option value="Toàn bộ" <?php if($Meal=="Toàn bộ") echo "selected"; ?>>Toàn bộ</option>
                    </select>
                    <div class="datesection">
                        <span>
                            <label for="cin">Check-In</label>
                            <input name="cin" type="date" value="<?php echo $cin ?>" required>
                        </span>
                        <span>
                            <label for="cout">Check-Out</label>
                            <input name="cout" type="date" value="<?php echo $cout ?>" required>
                        </span>
                    </div>
                    <p style="color: #666; margin-top: 10px;">
                        <i class="fa-solid fa-info-circle"></i> 
                        Current stay: <?php echo $noofdays ?> days
                    </p>
                </div>
            </div>
            <div class="footer">
                <button class="btn btn-success btn-lg" name="paymentedit">
                    <i class="fa-solid fa-save"></i> Update Payment
                </button>
                <a href="./index.php" class="btn btn-secondary btn-lg">
                    <i class="fa-solid fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>

