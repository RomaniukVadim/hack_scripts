unit Inject;

interface

  function InjectLib(const ADllPath: AnsiString; ProcessID: Integer): Boolean;
  function SetSeDebugPrivilege: Boolean;

implementation

uses
  Windows, WrapFunc, GlobalVar;


{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function InjectLib(const ADllPath: AnsiString; ProcessID: Integer): Boolean;
var
  Process: HWND;
  DllPath: AnsiString;
  ThreadRtn: FARPROC;
  RemoteDll: Pointer;
  BytesWriten: DWORD;
  Thread: DWORD;
  {Err, }ThreadId: DWORD;
begin
  Result := False;

  // Открываем процесс
  Process := _OpenProcess(PROCESS_ALL_ACCESS, True, ProcessID);
  if Process = 0 then Exit;
  try
    // Выделяем в нем память под строку
    DllPath := AnsiString(ADllPath) + #0;
    RemoteDll := VirtualAllocEx(Process, nil, Length(DllPath),
                                MEM_COMMIT or MEM_TOP_DOWN, PAGE_READWRITE);
    if RemoteDll = nil then Exit;
    try
      // Пишем путь к длл в его адресное пространство
      if not _WriteProcessMemory(Process, RemoteDll, PChar(DllPath),
                                Length(DllPath), BytesWriten) then Exit;
      if BytesWriten <> DWORD(Length(DllPath)) then Exit;
      // Получаем адрес функции из Kernel32.dll
      ThreadRtn := _GetProcAddress(dll_kernel32, fnc_LoadLibraryA);
      if ThreadRtn = nil then Exit;
      // Запускаем удаленный поток
      Thread := _CreateRemoteThread(Process, nil, 0, ThreadRtn, RemoteDll, 0, ThreadId);
      if Thread = 0 then
        begin
          //Err := GetLastError;
          Exit;
        end;
      try
        // Ждем пока удаленный поток отработает...
        Result := WaitForSingleObject(Thread, INFINITE) = WAIT_OBJECT_0;
      finally
        CloseHandle(Thread);
      end;
    finally
      VirtualFreeEx(Process, RemoteDll, 0, MEM_RELEASE);
    end;
  finally
    CloseHandle(Process);
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function SetSeDebugPrivilege: Boolean;
//var
//  ht: THandle;
//  luid: TLargeInteger;
//  tkp: TTokenPrivileges;
//  rl: Cardinal;
begin
  Result := False;
//  if OpenProcessToken(GetCurrentProcess, TOKEN_ADJUST_PRIVILEGES, ht) then
//    begin
//      LookupPrivilegeValue(nil, 'SeDebugPrivilege', luid);
//      tkp.Privileges[0].Luid := luid;
//      tkp.PrivilegeCount := 1;
//      tkp.Privileges[0].Attributes := SE_PRIVILEGE_ENABLED;
//      if AdjustTokenPrivileges(ht, false, tkp, 0, nil, rl) then
//         Result := True
//      else
//         Result := False;
//    end
//  else
//    Result := False;
end;


end.
