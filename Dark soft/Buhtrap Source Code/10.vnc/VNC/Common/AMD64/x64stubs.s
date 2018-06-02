;//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
;// VNC project. Version 1.9.17.3
;//	
;// module: w64stubs.asm
;// $Revision: 178 $
;// $Date: 2012-07-20 18:23:46 +0400 (Пт, 20 июл 2012) $
;// description: 
;//	AMD64 context stubs.


ASM_FIELDS			STRUC
HookFn				dq	?
Context				dq	?
ASM_FIELDS			ENDS

;------------------------------------------------------------------------------
;	MISC MACROS
;

DESCRIBE_PUBLIC		MACRO name:req, pname:req
					name	dq	pname	
					public	name
					ENDM

_TEXT segment


;// WOW64 inject context stub.
;// Receives pointer to INJECT_CONTEXT structure in RAX
Win64InjectStub	proc
	push [rax]		;// retpoint

	push rcx
	push rdx
	push r8
	push r9		;// push r9

	push rbp
	mov rbp, rsp
	sub rsp, 30h
	and rsp, 0fffffffffffffff0h

	mov rdx, [rax+8]
	mov rcx, [rax+10h]

	call rdx

	mov rsp, rbp

	pop rbp
	
	pop r9
	pop r8
	pop rdx
	pop rcx
	ret
Win64InjectStub	endp


Wow64InjectStub	proc
	push [rax]
	mov edx, [rax+8]
	mov ecx, [rax+10h]

	push rcx
	call rdx
	ret
Wow64InjectStub endp

; 4 arguments function hook
asmfields4 ASM_FIELDS <>
CallStub4_START LABEL PTR PROC 
RIPFIX4 = $ - CallStub4_START
_CallStub4	PROC
	sub     rsp, 28h
	mov     [rsp+20h], r9
	mov     r9, r8          ; a4
	mov     r8, rdx         ; a3
	mov     rdx, rcx        ; a2
	mov     rcx, [asmfields4.Context + RIPFIX4] ; a1
	call    [asmfields4.HookFn + RIPFIX4]
	add     rsp, 28h
	ret
_CallStub4	ENDP
CallStub4_END LABEL PTR PROC

DESCRIBE_PUBLIC CallStub4, CallStub4_START
DESCRIBE_PUBLIC CallStub4SIZE, <CallStub4_END - CallStub4_START>

_TEXT ends

public	Win64InjectStub

end
