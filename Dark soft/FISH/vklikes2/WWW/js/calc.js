

$(document).ready(function(){




var sums;
var days;
sums = 1000;
days = 2;

function summ(sums) {
sums = sums;
}

function dayss(days) {
dayss = dayss;
}

function resultcalc(summ, days) {

if(days == 1) {
percent = '7';
day = 30;
}
				
else if(days == 2) {
percent = '8';
day = 90;
}

else if(days == 3) {
percent = '10';
day = 180;
}

else if(days == 4) {
percent = '13';
day = 360;
}

profit = ((summ * (percent) * (day) / 30 ) / 100);
$( "#profit" ).html(profit);
$( "#percent" ).html(percent);
$( "#total" ).html(profit+summ);
$( "#sum1" ).html(summ);



}



$( ".days" ).slider({
        animate: true, // Анимация ползунка
            range: "min", // Фон пути ползунка, если это свойство убрать, то синей линии не будет.
            value: 2, // Значение по умолчанию.
            min: 1, // Минимальная сумма.
            max: 4, // Максимальная сумма.
			step: 1, // Шаг диапазона.
 
    // Вывод диапазона
            slide: function( event, ui ) {
            
            	days = ui.value;
            	
            	if(ui.value == 1) {
            	planname = 'tarif 1';
            	//$( ".slider" ).slider('value', 100);

            	}
				else if(ui.value == 2) {
				planname = 'tarif 2';
				//$( ".slider" ).slider('value', 500);
				}
				
				else if(ui.value == 3) {
				planname = 'tarif 3';
				//$( ".slider" ).slider('value', 1000);
				}
				
				else if(ui.value == 4) {
				planname = 'tarif 4';
				//$( ".slider" ).slider('value', 500);
				}
				
                $( "#plan" ).html(planname);
                
                sums = $( ".slider" ).slider('value');
                resultcalc(sums, days);
                
               
                
            },
 
 
 
     });



              // Указываем class блока div где будет ползунок.
     $( ".slider" ).slider({
        animate: true, // Анимация ползунка
            range: "min", // Фон пути ползунка, если это свойство убрать, то синей линии не будет.
            value: 1000, // Значение по умолчанию.
            min: 100, // Минимальная сумма.
            max:5000, // Максимальная сумма.
    step: 5, // Шаг диапазона.
 
    // Вывод диапазона
            slide: function( event, ui ) {
                $( "#sum1" ).html(ui.value);
                
                sums = ui.value;
                summ(sums);
                
                
             

                
                
                
                resultcalc(sums, days);
            },
 

     });
     
     
     resultcalc(sums, days);
  });
