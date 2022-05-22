<html> 
<head>
  <title>K114</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width", initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script src="http://fred-wang.github.io/mathml.css/mspace.js"></script>
<style>
ul { 
 text-align: center;
 list-style-type: none;
 margin: 0;
 padding: 0;
}
li {
 display: inline-block;
 margin-left: auto;
 margin-right: auto;
}
a { 
 padding: 8px;
 background-color:linen;
 display: block;
}
.center {
   width: 300px;
   height: 300px;
   position: absolute;
   left: 50%;
   top: 50%; 
   margin-left: -150px;
   margin-top: -150px;
} #countbox1 {
font: 18pt Arial; 
color: #ffffff; 
text-align: center; 
position: relative; 
top: 50%; 
transform: translateY(-5%);
}
#button { 
text-align: center; 
position: relative; 
height: 300px;
position: relative;
top: 50%; 
transform: translateY(10%);
}
@-moz-document url-prefix() {
   #countbox1 {  
font: 18pt Arial; 
color: #ffffff; 
text-align: center; 
height: 300px; 
position: relative; 
top: 50%; 
transform: translateY(100%);
}
#button { 
text-align: center; 
top: 50%; 
position: relative;
transform: translateY(20%);
}
}
</style>
</head> 
<script type="text/javascript">
//###################################################################
// Author: ricocheting.com
// Version: v3.1
// Date: 2017-01-03
// Description: displays the amount of time until the "dateFuture" entered below.

var CDown = function() {
	this.state=0;// if initialized
	this.counts=[];// array holding countdown date objects and id to print to {d:new Date(2013,11,18,18,54,36), id:"countbox1"}
	this.interval=null;// setInterval object
}

CDown.prototype = {
	init: function(){
		this.state=1;
		var self=this;
		this.interval=window.setInterval(function(){self.tick();}, 1000);
	},
	add: function(date,id){
		this.counts.push({d:date,id:id});
		this.tick();
		if(this.state==0) this.init();
	},
	expire: function(idxs){
		for(var x in idxs) {
			this.display(this.counts[idxs[x]], "Now!");
			this.counts.splice(idxs[x], 1);
		}
	},
	format: function(r){
		var pre='',post='',divide=', ',
			out="";
		if(r.d != 0){out += pre+r.d +" "+((r.d==1)?"day":"days")+post+divide;}
		if(r.h != 0){out += pre+r.h +" "+((r.h==1)?"hour":"hours")+post+divide;}
		out += pre+r.m +" "+((r.m==1)?"min":"mins")+post+divide;
		out += pre+r.s +" "+((r.s==1)?"sec":"secs")+post+divide;

		return out.substr(0,out.length-divide.length);
	},
	math: function(work){
		var	y=w=d=h=m=s=ms=0;

		ms=(""+((work%1000)+1000)).substr(1,3);
		work=Math.floor(work/1000);//kill the "milliseconds" so just secs

		y=Math.floor(work/31536000);//years (no leapyear support)
		w=Math.floor(work/604800);//weeks
		d=Math.floor(work/86400);//days
		work=work%86400;

		h=Math.floor(work/3600);//hours
		work=work%3600;

		m=Math.floor(work/60);//minutes
		work=work%60;

		s=Math.floor(work);//seconds

		return {y:y,w:w,d:d,h:h,m:m,s:s,ms:ms};
	},
	tick: function(){
		var now=(new Date()).getTime(),
			expired=[],cnt=0,amount=0;

		if(this.counts)
		for(var idx=0,n=this.counts.length; idx<n; ++idx){
			cnt=this.counts[idx];
			amount=cnt.d.getTime()-now;//calc milliseconds between dates

			// if time is already past
			if(amount<0){
				expired.push(idx);
			}
			// date is still good
			else{
				this.display(cnt, this.format(this.math(amount)));
			}
		}

		// deal with any expired
		if(expired.length>0) this.expire(expired);

		// if no active counts, stop updating
		if(this.counts.length==0) window.clearTimeout(this.interval);
		
	},
	display: function(cnt,msg){
		document.getElementById(cnt.id).innerHTML=msg;
	}
};

window.onload=function(){
	var cdown = new CDown();

	cdown.add(new Date(2017,11,20,23,59,0), "countbox1");
};
</script>
<body style="background-color:#000000">
<div id="countbox1"></div>
<div id=button>
   <input type="button" onClick="window.location='index.php';" value="Continue">
</div>
</body>
</html>
