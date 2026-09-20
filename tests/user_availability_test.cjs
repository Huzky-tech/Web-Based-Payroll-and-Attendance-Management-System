const fs = require('fs'), vm = require('vm'), assert = require('assert/strict');
let passed = 0;
function field(value='') {
 const classes = new Set();
 return {value,dataset:{},textContent:'',children:[], classList:{toggle(k,on){if(on)classes.add(k);else classes.delete(k);},remove(k){classes.delete(k);},add(k){classes.add(k);},contains:k=>classes.has(k)},setCustomValidity(v){this.validationMessage=v;},setAttribute(){},append(...nodes){this.children.push(...nodes);}};
}
async function run(file) {
 const source = fs.readFileSync(file,'utf8');new vm.Script(source);
 const fields={};for(const prefix of ['newUser','editUser']) for(const part of ['FirstName','LastName','Email','FirstNameError','LastNameError','EmailError','Id'])fields[prefix+part]=field();
 fields.addUserSubmitBtn=field();fields.editUserSubmitBtn=field();fields.newUserRole=field('Payroll Staff');
 const timers=[];let result={success:true,available:true,name_available:true};let offline=false;let lastUrl='';
 const window={};
 const context=vm.createContext({window,document:{getElementById:id=>fields[id],createElement:()=>field()},Map,Date,JSON,URLSearchParams,AbortController,FormData,console,
  setTimeout:(fn,ms)=>{if(ms===250)timers.push(fn);return 1;},clearTimeout(){},
  fetch:async url=>{lastUrl=url;if(offline)throw Error('offline');return {ok:true,json:async()=>result};},userAllUsersData:[]});
 const start=source.indexOf('function validateUserIdentityFields('),end=source.indexOf('\nfunction ',start+1);
 vm.runInContext(source.slice(start,end),context);
 window.validateUserIdentityFields=context.validateUserIdentityFields;
 vm.runInContext(fs.readFileSync('js/user_email_validation.js','utf8'),context);
 fields.newUserFirstName.value='John';fields.newUserLastName.value='Vruz';fields.newUserEmail.value='john@example.test';
 const validate=()=>context.validateUserIdentityFields('new');
 assert.equal(validate(),false);passed++;await timers.shift()();
 assert.equal(validate(),true);passed++;
 result={success:true,available:true,name_available:false};fields.newUserLastName.value='Cruz';
 assert.equal(validate(),false);passed++;await timers.shift()();assert.equal(fields.addUserSubmitBtn.disabled,true);passed++;
 assert.match(fields.newUserLastNameError.textContent,/already registered/);passed++;
 result={success:true,available:false,name_available:true};window.resetUserEmailAvailability('new');validate();await timers.shift()();
 assert.match(fields.newUserEmailError.textContent,/already registered/);passed++;
 assert.equal(fields.newUserEmail.classList.contains('field-valid'),false);passed++;
 offline=true;window.resetUserEmailAvailability('new');validate();await timers.shift()();
 assert.equal(validate(),false);passed++;assert.equal(fields.newUserEmail.classList.contains('field-valid'),false);passed++;
 assert.match(fields.newUserEmailError.textContent,/retry/);passed++;
 offline=false;result={success:true,available:true,name_available:true};
 fields.newUserEmailError.children.find(x=>typeof x==='object').onclick();await timers.shift()();assert.equal(validate(),true);passed++;
 fields.editUserId.value='42';fields.editUserFirstName.value='John';fields.editUserLastName.value='Vruz';fields.editUserEmail.value='john@example.test';
 context.validateUserIdentityFields('edit');await timers.shift()();assert.equal(context.validateUserIdentityFields('edit'),true);passed++;
 assert.match(lastUrl,/exclude_id=42/);passed++;
 // Verify feedback isn't delayed by a hung directory refresh.
 const addStart=source.indexOf('async function handleAddUser('),addEnd=source.indexOf('let userLoadPromise',addStart);
 vm.runInContext(source.slice(addStart,addEnd),context);
 context.validateUserIdentityFields=()=>true;
 const events=[];window.showProcessingModal=()=>events.push('processing');
 context.fetchJson=async()=>({success:true,message:'Created'});context.closeAddUserModal=()=>events.push('close');context.showSettingsActionResult=(ok,msg)=>events.push(ok?'success':'error');
 let release;context.loadUsers=()=>new Promise(resolve=>{events.push('refresh');release=resolve;});
 const pending=context.handleAddUser();await new Promise(setImmediate);
 assert.deepEqual(events,['processing','close','success','refresh']);passed++;
 release();await pending;
 context.fetchJson=async()=>({success:false,message:'Already registered'});events.length=0;await context.handleAddUser();
 assert.deepEqual(events,['processing','error']);passed++;
}
(async()=>{await run('js/employee.js');await run('js/setting.js');console.log(`PASS: ${passed} availability and submission feedback checks.`);})().catch(e=>{console.error(e);process.exitCode=1;});
