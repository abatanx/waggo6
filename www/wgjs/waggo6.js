/**
 * waggo6
 * @copyright 2013-2020 CIEL, K.K., project waggo.
 * @author CIEL, K.K.
 * @license MIT
 **/

var WG6 = function(){};
WG6.opts = function(opts)
{
	return opts==null ? {} : opts;
};
WG6.beforeLoad = function(jqs) {};
WG6.afterLoad = function(jqs) {};

WG6.remakeURI = function(uri,opts)
{
	var k,p,a,t,r,q,u;
	r = [];
	k = {};
	p = (uri+"?").split("?");
	a = p[1].split("&");
	$.each(a, function(i,v) {
		t = v.split("=");
		pk = t.length > 0 ? decodeURIComponent(t[0].replace(/\+/g, '%20')) : '';
		pv = t.length > 1 ? decodeURIComponent(t[1].replace(/\+/g, '%20')) : '';
		k[pk] = t.length > 1 ? pv : "";
	});
	$.each(opts, function(pk,pv) { k[pk] = pv; });
	$.each(k, function(pk,pv) {
		if(pk!=null && pv!=null)
		{
			var     qk=encodeURIComponent(pk), qv=encodeURIComponent(pv);
			r.push( qk + (qv!="" ? "=" + qv : "") );
		}
	});
	q = r.join("&");
	u = (r!="") ? p[0] + "?" + q : p[0];

	return u;
};

WG6.parse = function(jqs, jqx, u, opts)
{
	try
	{
		opts = WG6.opts(opts);

		var d=[], s=[];
		var x = jqx.responseXML;
		for ( var el = x.documentElement; el != null; el = el.nextSibling) {
			if (el.nodeType === 1 && el.nodeName === 'result' && el.hasChildNodes()) {
				for ( var el0 = el.firstChild; el0 != null; el0 = el0.nextSibling) {
					if (el0.nodeType === 1 && el0.nodeName === 'code' && el0.hasChildNodes()) {
						for ( var el1 = el0.firstChild; el1 != null; el1 = el1.nextSibling)
							if (el1.nodeType === 3) d['code'] = el1.nodeValue;
					}
					if (el0.nodeType === 1 && el0.nodeName === 'location' && el0.hasChildNodes()) {
						for ( var el1 = el0.firstChild; el1 != null; el1 = el1.nextSibling)
							if (el1.nodeType === 3) d['location'] = el1.nodeValue;
					}
					if (el0.nodeType === 1 && el0.nodeName === 'template' && el0.hasChildNodes()) {
						for ( var el1 = el0.firstChild; el1 != null; el1 = el1.nextSibling)
							if (el1.nodeType === 4) d['template'] = el1.nodeValue;
						if (el0.getAttribute('type') !== undefined) d['template.type'] = el0.getAttribute('type');
						if (el0.getAttribute('action') !== undefined) d['template.action'] = el0.getAttribute('action');
					}
					if (el0.nodeType === 1 && el0.nodeName === 'script' && el0.hasChildNodes()) {
						var src = '', ev = '';
						for ( var el1 = el0.firstChild; el1 != null; el1 = el1.nextSibling) if (el1.nodeType === 3) src += el1.nodeValue;
						ev = (el0.getAttribute('event') !== undefined) ? el0.getAttribute('event') : '';
						s.push( {event:ev.toLowerCase(), src:src });
					}
				}
			}
		}
	}
	catch (e) {
		console.log(e.error);
	}

	// Location
	if (d['location'] !== undefined) {
		if (d.get['location'] === 'reload') window.location.reload();
		else WG6.get(jqs, d['location'], opts);
		return;
	}

	// JavaScript EventTrigger
	s.forEach(function(o){if(o.event === 'onpreload') eval(o.src);});

	if( d['template.action']!==undefined )
		$(jqs).attr('data-wg-url',d['template.action']);
	else
		$(jqs).attr('data-wg-url',u);

	switch(d['template.type'])
	{
		case 'text/html':
			$(jqs).html(d['template']);
			break;
		default:
			$(jqs).text(d['template']);
			break;
	}

	// JavaScript EventTrigger
	s.forEach(function(o){if(o.event === 'onloaded') eval(o.src);});
};

WG6.get = function(jqs,url,opts) {
	opts = WG6.opts(opts);
	$.ajax({
		url: url, method: 'GET', dataType: 'xml',
		beforeSend: function () {
			if (opts.beforeSend != null) opts.beforeSend(jqs);
			WG6.beforeLoad(jqs);
		},
		complete: function (jqx) {
			WG6.afterLoad(jqs);
			WG6.parse(jqs, jqx, url, opts);
		},
		error: function () {
			WG6.afterLoad(jqs);
			console.log('WG6.get failed, ' + url);
		}
	});
};

WG6.post = function(jqs,url,opts) {
	opts = WG6.opts(opts);
	var post = $(jqs).find('input,textarea,select').serialize();
	$.ajax({
		url: url, method: 'POST', data: post, dataType: 'xml',
		beforeSend: function () {
			if (opts.beforeSend != null) opts.beforeSend(jqs);
			WG6.beforeLoad(jqs);
		},
		complete: function (jqx) {
			WG6.afterLoad(jqs);
			WG6.parse(jqs, jqx, url, opts);
		},
		error: function () {
			WG6.afterLoad(jqs);
			console.log('WG6.post failed, ' + url);
		}
	});
};

WG6.reget = function(jqs,opts) {
	opts = WG6.opts(opts);
	$(jqs).each(function(i,q)
	{
		var t = $(q).closest('.wg-form');
		if( t.attr('data-wg-url')!==undefined && t.attr('data-wg-url')!=='' ) WG6.get(t,t.attr('data-wg-url'),opts);
	});
};

WG6.repost = function(jqs,opts) {
	opts = WG6.opts(opts);
	$(jqs).each(function(i,q)
	{
		var t = $(q).closest('.wg-form');
		if( t.attr('data-wg-url')!==undefined && t.attr('data-wg-url')!=='' ) WG6.post(t,t.attr('data-wg-url'),opts);
	});
};

WG6.reload = function(jqs,opts) {
	WG6.reget(jqs,opts);
};

WG6.closestForm = function(jqs) {
	return jqs.closest('.wg-form');
};
