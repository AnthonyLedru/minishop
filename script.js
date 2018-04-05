
function pop(id) {
	document.getElementById("overlay").classList.remove("none");
	document.getElementById(id).style.display = "flex";
	document.getElementById("overlay").close = id;
	return false;
}

function del(elem) {
	elem.classList.add('none');
	if (elem.close)
		document.getElementById(elem.close).style.display = 'none';
	return false;
}

function convert_post_link() {
	history.pushState('','');
	document.querySelectorAll("a.post").forEach((e)=>(
		e.onclick = function() {
			var mform = document.createElement('form');
			var data = e.href.split('?');
			var params = data[1].split('&');
			var pp, minput;
			
			mform.method = 'post';
			mform.action = data[0];
			for(var i = 0, n = params.length; i < n; i++) {
				pp = params[i].split('=');
				minput = document.createElement('input');
				minput.type = 'hidden';
				minput.name = pp[0];
				minput.value = pp[1];
				mform.appendChild(minput);
			}
			document.body.appendChild(mform);
			mform.submit();
			return false;
		}
	));
	document.querySelectorAll("a.submit").forEach((e)=>(
		e.onclick = function() {
			var id = e.href.split('?')[1].split('=')[0];
			document.querySelector("#"+id+" input[name='page']").value = e.href.split('=')[1];
			document.getElementById(id).submit();
			return false;
		}
	));
};

if (document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll))
	convert_post_link();
else
	document.addEventListener("DOMContentLoaded", convert_post_link);
