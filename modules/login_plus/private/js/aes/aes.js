!function(e,r,i){"object"==typeof exports?module.exports=exports=r(require("./core"),require("./enc-base64"),require("./md5"),require("./evpkdf"),require("./cipher-core")):"function"==typeof define&&define.amd?define(["./core","./enc-base64","./md5","./evpkdf","./cipher-core"],r):r(e.CryptoJS)}(this,function(e){return function(){var r=e,i=r.lib,o=i.BlockCipher,t=r.algo,n=[],c=[],s=[],f=[],a=[],d=[],u=[],v=[],h=[],y=[];!function(){for(var e=[],r=0;r<256;r++)r<128?e[r]=r<<1:e[r]=r<<1^283;for(var i=0,o=0,r=0;r<256;r++){var t=o^o<<1^o<<2^o<<3^o<<4;t=t>>>8^255&t^99,n[i]=t,c[t]=i;var p=e[i],l=e[p],_=e[l],k=257*e[t]^16843008*t;s[i]=k<<24|k>>>8,f[i]=k<<16|k>>>16,a[i]=k<<8|k>>>24,d[i]=k;var k=16843009*_^65537*l^257*p^16843008*i;u[t]=k<<24|k>>>8,v[t]=k<<16|k>>>16,h[t]=k<<8|k>>>24,y[t]=k,i?(i=p^e[e[e[_^p]]],o^=e[e[o]]):i=o=1}}();var p=[0,1,2,4,8,16,32,64,128,27,54],l=t.AES=o.extend({_doReset:function(){if(!this._nRounds||this._keyPriorReset!==this._key){for(var e=this._keyPriorReset=this._key,r=e.words,i=e.sigBytes/4,o=this._nRounds=i+6,t=4*(o+1),c=this._keySchedule=[],s=0;s<t;s++)if(s<i)c[s]=r[s];else{var f=c[s-1];s%i?i>6&&s%i==4&&(f=n[f>>>24]<<24|n[f>>>16&255]<<16|n[f>>>8&255]<<8|n[255&f]):(f=f<<8|f>>>24,f=n[f>>>24]<<24|n[f>>>16&255]<<16|n[f>>>8&255]<<8|n[255&f],f^=p[s/i|0]<<24),c[s]=c[s-i]^f}for(var a=this._invKeySchedule=[],d=0;d<t;d++){var s=t-d;if(d%4)var f=c[s];else var f=c[s-4];d<4||s<=4?a[d]=f:a[d]=u[n[f>>>24]]^v[n[f>>>16&255]]^h[n[f>>>8&255]]^y[n[255&f]]}}},encryptBlock:function(e,r){this._doCryptBlock(e,r,this._keySchedule,s,f,a,d,n)},decryptBlock:function(e,r){var i=e[r+1];e[r+1]=e[r+3],e[r+3]=i,this._doCryptBlock(e,r,this._invKeySchedule,u,v,h,y,c);var i=e[r+1];e[r+1]=e[r+3],e[r+3]=i},_doCryptBlock:function(e,r,i,o,t,n,c,s){for(var f=this._nRounds,a=e[r]^i[0],d=e[r+1]^i[1],u=e[r+2]^i[2],v=e[r+3]^i[3],h=4,y=1;y<f;y++){var p=o[a>>>24]^t[d>>>16&255]^n[u>>>8&255]^c[255&v]^i[h++],l=o[d>>>24]^t[u>>>16&255]^n[v>>>8&255]^c[255&a]^i[h++],_=o[u>>>24]^t[v>>>16&255]^n[a>>>8&255]^c[255&d]^i[h++],k=o[v>>>24]^t[a>>>16&255]^n[d>>>8&255]^c[255&u]^i[h++];a=p,d=l,u=_,v=k}var p=(s[a>>>24]<<24|s[d>>>16&255]<<16|s[u>>>8&255]<<8|s[255&v])^i[h++],l=(s[d>>>24]<<24|s[u>>>16&255]<<16|s[v>>>8&255]<<8|s[255&a])^i[h++],_=(s[u>>>24]<<24|s[v>>>16&255]<<16|s[a>>>8&255]<<8|s[255&d])^i[h++],k=(s[v>>>24]<<24|s[a>>>16&255]<<16|s[d>>>8&255]<<8|s[255&u])^i[h++];e[r]=p,e[r+1]=l,e[r+2]=_,e[r+3]=k},keySize:8});r.AES=o._createHelper(l)}(),e.AES});
//# sourceMappingURL=aes.min.js.map