/* IA: Codex GPT-6.1-sol | 2026-11-01. Revisar frontend local en navegador aislado.
 * No usa perfil/sesion de Chrome del usuario. Bloquea POST y sitios externos; solo captura QA local.
 */
'use strict';
const {chromium} = require('C:/xampp/htdocs/frontend/node_modules/playwright');
const assert = require('node:assert/strict');
const path = require('node:path');
const fs = require('node:fs');
(async () => {
  const output = path.resolve(__dirname,'../editorial/blog_piton_bola/qa_local');
  fs.mkdirSync(output,{recursive:true});
  const browser = await chromium.launch({headless:true,executablePath:'C:/Users/aleja/AppData/Local/ms-playwright/chromium_headless_shell-1228/chrome-headless-shell-win64/chrome-headless-shell.exe',args:['--host-resolver-rules=MAP artiani.com.local 127.0.0.1, MAP panel.com.local 127.0.0.1']});
  const results=[];
  try {
    for(const [name,width,height] of [['desktop',1440,1000],['mobile',390,844]]) {
      const ctx=await browser.newContext({viewport:{width,height}});
      await ctx.route('**/*',route=>{
        const req=route.request(),url=new URL(req.url());
        if(!['GET','HEAD'].includes(req.method()))return route.fulfill({status:200,contentType:'application/json',body:'{"error":false,"qa_mock":true}'});
        if(!['artiani.com.local','panel.com.local'].includes(url.hostname))return route.fulfill({status:204,body:''});
        return route.continue();
      });
      const page=await ctx.newPage();
      const r=await page.goto('http://artiani.com.local/blog/piton-bola-preparar-habitat',{waitUntil:'domcontentloaded',timeout:60000});
      assert.equal(r.status(),200);
      await page.locator('[data-blog-detail]').waitFor();
      await page.locator('[data-blog-detail] img[src*="piton-bola"]').evaluateAll(async imgs=>{
        for(const img of imgs){img.loading='eager';if(!img.complete)await new Promise(resolve=>{img.addEventListener('load',resolve,{once:true});img.addEventListener('error',resolve,{once:true});});}
      });
      const images=await page.locator('[data-blog-detail] img[src*="piton-bola"]').evaluateAll(imgs=>imgs.map(i=>({src:i.currentSrc||i.src,width:i.naturalWidth,height:i.naturalHeight,complete:i.complete})));
      assert.equal(images.length,7);assert(images.every(i=>i.complete&&i.width===1536&&i.height===1024));
      assert.equal(await page.locator('[data-blog-interactive]').count(),2);
      assert.equal(await page.locator('[data-blog-hotspot]').count(),6);
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+2);
      await page.screenshot({path:path.join(output,name+'.png'),fullPage:false});
      results.push({view:name,status:200,imagenes:images.length,imagenes_distintas:new Set(images.map(i=>i.src)).size,todas_cargadas:true,overflow});
      await ctx.close();
    }
    console.log(JSON.stringify({ok:true,resultados:results,post_reales:0,output},null,2));
  }finally{await browser.close();}
})().catch(e=>{console.error(e.message);process.exitCode=1;});
