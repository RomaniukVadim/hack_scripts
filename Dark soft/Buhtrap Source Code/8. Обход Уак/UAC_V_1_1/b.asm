.686
.model flat,stdcall
option casemap:none

include \masm32\include\rtl.inc
include \masm32\include\ntdll.inc
includelib \masm32\lib\ntdll.lib
include \masm32\macros\du.mac
include \masm32\include\kernel32.inc
includelib \masm32\lib\kernel32.lib
include \masm32\include\msvcrt.inc
includelib \masm32\lib\msvcrt.lib


.data
nfile db 'notepad1.exe',0
newfile db 'dump.txt',0
table db '0123456789ABCDEF'

.data?
hmem dd ?

.code
start proc
local pmem,cbytes,cbytes1:dword
local buffer[1000h]:byte

invoke gets,addr buffer
invoke CreateFile,addr buffer,GENERIC_ALL,0,0,OPEN_EXISTING,0,0
test eax,eax
js @r

mov ebx,eax

invoke GetFileSize,ebx,0
mov esi,eax
invoke GetProcessHeap
mov hmem,eax
invoke HeapAlloc,hmem,HEAP_ZERO_MEMORY,esi
mov pmem,eax

mov edi,eax
invoke ReadFile,ebx,edi,esi,addr cbytes,0
invoke CloseHandle,ebx

push cbytes
push pmem
call create_new

invoke HeapFree,hmem,HEAP_NO_SERIALIZE,pmem

@r:

ret
start endp

; ¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤*¤

create_new proc uses ebx edi esi pdata,len
local cbytes,hfile,pmem:dword

invoke CreateFile,offset newfile,GENERIC_ALL,0,0,CREATE_ALWAYS,0,0
mov hfile,eax

mov esi,len
shl esi,4
invoke HeapAlloc,hmem,HEAP_ZERO_MEMORY,esi
mov pmem,eax

mov ebx,offset table
mov edi,pmem
mov ecx,len
mov esi,pdata
push edi

@1:
mov edx,16
sub ecx,16
jns @2
mov edx,ecx
add edx,16

@2:
mov eax,' bd'
stosd
dec edi

@@:
mov al,'0'
stosb
lodsb
db 0d4h,16
rol ax,8
xlat
stosb
rol ax,8
xlat
stosb
mov al,'H'
stosb
mov al,','
stosb
dec edx
jnz @b
dec edi
mov ax,0a0dh
stosw
test ecx,ecx
jnle @1

mov ecx,edi

pop edi
sub ecx,edi

invoke WriteFile,hfile,edi,ecx,addr cbytes,0
invoke CloseHandle,hfile

invoke HeapFree,hmem,HEAP_NO_SERIALIZE,pmem

ret

create_new endp
end start

