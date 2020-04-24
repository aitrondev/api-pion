<!DOCTYPE html>
<html>
    <head>
        <script type="text/javascript" src="../js/jquery-2.2.4.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function(){
                console.log(1)
                $.ajax({
                    method : "GET",
                    url : "/puup/faq"
                }).done(function(data){
                    console.log(data.data.faq)
                    var j = data.data.faq;
                    console.log(data.data.faq.length)  
                    for(var i=0;i<j.length;i++){
                        
                        var c = "<div>";
                        	c+="<input id='ac-"+i+"' name='accordion-"+i+"' type='checkbox' />";
                        	c+="<label for='ac-"+i+"'>"+j[i].pertanyaan+"</label>";
                        	c+="<article class='ac-small'>";
                        	c+="<p>"+j[i].jawaban+"</p>";
                        	c+="</article>";
                        	c+="</div>";
                        $(c).appendTo(".ac-container");
                    }
                })
            })
        </script>
    </head>
    <section class="ac-container">
	
    </section>
<style type="text/css">
    

.ac-container{
	width: 100%;
	margin: 10px auto 30px auto;
}
.ac-container label{
	font-family: 'BebasNeueRegular', 'Arial Narrow', Arial, sans-serif;
	padding: 5px 20px;
	position: relative;
	z-index: 20;
	display: block;
	height: 30px;
	cursor: pointer;
	color: #777;
	text-shadow: 1px 1px 1px rgba(255,255,255,0.8);
	line-height: 33px;
	font-size: 19px;
	background: linear-gradient(top, #ffffff 1%,#eaeaea 100%);
	box-shadow: 
		0px 0px 0px 1px rgba(155,155,155,0.3), 
		1px 0px 0px 0px rgba(255,255,255,0.9) inset, 
		0px 2px 2px rgba(0,0,0,0.1);
}
.ac-container label:hover{
	background: #fff;
}
.ac-container input:checked + label,
.ac-container input:checked + label:hover{
	background: #ce292f;
	color: #f6f8f9;
	text-shadow: 0px 1px 1px rgba(255,255,255, 0.6);
	box-shadow: 
		0px 0px 0px 1px rgba(155,155,155,0.3), 
		0px 2px 2px rgba(0,0,0,0.1);
}
.ac-container label:hover:after,
.ac-container input:checked + label:hover:after{
	content: '';
	position: absolute;
	width: 24px;
	height: 24px;
	right: 13px;
	top: 7px;
	background: transparent url(../images/arrow_down.png) no-repeat center center;	
}
.ac-container input:checked + label:hover:after{
	background-image: url(../images/arrow_up.png);
}
.ac-container input{
	display: none;
}
.ac-container article{
	background: rgba(255, 255, 255, 0.5);
	margin-top: -1px;
	overflow: hidden;
	height: 0px;
	position: relative;
	z-index: 10;
	transition: 
		height 0.3s ease-in-out, 
		box-shadow 0.6s linear;
}
.ac-container input:checked ~ article{
	transition: 
		height 0.5s ease-in-out, 
		box-shadow 0.1s linear;
	box-shadow: 0px 0px 0px 1px rgba(155,155,155,0.3);
}
.ac-container article p{
	font-style: italic;
	color: #777;
	line-height: 23px;
	font-size: 14px;
	padding: 20px;
	text-shadow: 1px 1px 1px rgba(255,255,255,0.8);
}
.ac-container input:checked ~ article.ac-small{
	height: 140px;
}
.ac-container input:checked ~ article.ac-medium{
	height: 180px;
}
.ac-container input:checked ~ article.ac-large{
	height: 230px;
}

</style>

</html>



