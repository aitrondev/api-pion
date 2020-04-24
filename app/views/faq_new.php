<!DOCTYPE html>
<html>
<head>
	<title></title>
	<link rel="stylesheet" href="../js/jqueryui/jquery-ui.css">
	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
	<script src="../js/jqueryui/jquery-ui.js"></script>
	<script type="text/javascript">
		$( function() {
		   

		    $.ajax({
                method : "GET",
                url : "/puup/faq"
            }).done(function(data){
                var j = data.data.faq;
                for(var i=0;i<j.length;i++){

                    var c = "<h3>"+j[i].pertanyaan+"</h3>";
                    	c+= "<div>"+j[i].jawaban+"</div>"; 
                    $(c).appendTo("#accordion");
                }

                 $( "#accordion" ).accordion({
			      heightStyle: "fill"
			    });
            })

	  	});
  </script>
	</script>
</head>
<body>
	<div id="accordion-resizer" class="ui-widget-content">
	  <div id="accordion">
	  </div>
	</div>
</body>
</html>