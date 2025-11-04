<?php

include '../../../config.php';

$id = $_GET['id'];

$sql ="Select * from roombook where id = '$id'";
$re = mysqli_query($conn,$sql);
while($row=mysqli_fetch_array($re))
{
	$Name = $row['Name'];
    $Email = $row['Email'];
    $Country = $row['Country'];
    $Phone = $row['Phone'];
    $RoomType = $row['RoomType'];
    $Bed = $row['Bed'];
    $NoofRoom = $row['NoofRoom'];
    $Meal = $row['Meal'];
    $cin = $row['cin'];
    $cout = $row['cout'];
    $noofday = $row['nodays'];
    $stat = $row['stat'];
}


if($stat == "NotConfirm")
{
    $st = "Confirm";

    $sql = "UPDATE roombook SET stat = '$st' WHERE id = '$id'";
    $result = mysqli_query($conn,$sql);

    if($result){

        $type_of_room = 0;
        // ⚠️ GIÁ TEST (Tiền Trăm) - Phù hợp cho test MoMo Sandbox
        if($RoomType=="Phòng Cao Cấp")
        {
            $type_of_room = 500000; // 500k VND (test) - Production: 3,000,000
        }
        else if($RoomType=="Phòng Sang Trọng")
        {
            $type_of_room = 300000; // 300k VND (test) - Production: 2,000,000
        }
        else if($RoomType=="Nhà Khách")
        {
            $type_of_room = 200000; // 200k VND (test) - Production: 1,500,000
        }
        else if($RoomType=="Phòng Đơn")
        {
            $type_of_room = 100000; // 100k VND (test) - Production: 1,000,000
        }
        
        
        if($Bed=="Đơn")
        {
            $type_of_bed = $type_of_room * 1/100;
        }
        else if($Bed=="Đôi")
        {
            $type_of_bed = $type_of_room * 2/100;
        }
        else if($Bed=="Ba")
        {
            $type_of_bed = $type_of_room * 3/100;
        }
        else if($Bed=="Bốn")
        {
            $type_of_bed = $type_of_room * 4/100;
        }
            else if($Bed=="Không")
        {
            $type_of_bed = $type_of_room * 0/100;
        }

        if($Meal=="Chỉ phòng")
        {
            $type_of_meal=$type_of_bed * 0;
        }
        else if($Meal=="Bữa sáng")
        {
            $type_of_meal=$type_of_bed * 2;
        }
        else if($Meal=="Nửa suất")
        {
            $type_of_meal=$type_of_bed * 3;
        }
        else if($Meal=="Toàn bộ")
        {
            $type_of_meal=$type_of_bed * 4;
        }
                                                            
        $ttot = $type_of_room *  $noofday * $NoofRoom;
        $mepr = $type_of_meal *  $noofday;
        $btot = $type_of_bed * $noofday;

        $fintot = $ttot + $mepr + $btot;

        $psql = "INSERT INTO payment(id,Name,Email,RoomType,Bed,NoofRoom,cin,cout,noofdays,roomtotal,bedtotal,meal,mealtotal,finaltotal) VALUES ('$id', '$Name', '$Email', '$RoomType', '$Bed', '$NoofRoom', '$cin', '$cout', '$noofday', '$ttot', '$btot', '$Meal', '$mepr', '$fintot')";

        mysqli_query($conn,$psql);

        header("Location:index.php");
    }
}
// else
// {
//     echo "<script>alert('Guest Already Confirmed')</script>";
//     header("Location:roombook.php");
// }


?>