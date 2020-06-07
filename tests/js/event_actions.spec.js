import { fireEvent, wait } from 'dom-testing-library'
import testHarness from './fixtures/test_harness'
const timeout = ms => new Promise(resolve => setTimeout(resolve, ms))

test('basic click', async () => {
    var payload
    testHarness.mount({
        dom: '<button wire:click="someMethod"></button>',
        requestInterceptor: i => payload = i
    })

    document.querySelector('button').click()

    await wait(() => {
        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('someMethod')
        expect(payload.actionQueue[0].payload.params).toEqual([])
    })
})

test('basic click with self modifier', async () => {
    var payload
    testHarness.mount({
        dom: '<button wire:click.self="outerMethod"><span wire:click="innerMethod"></span></button>',
        requestInterceptor: i => payload = i
    })

    document.querySelector('span').click()

    await wait(() => {
        expect(payload.actionQueue[0].payload.method).toEqual('innerMethod')
        expect(payload.actionQueue[1]).toBeUndefined()
    })
})

test('click with params', async () => {
    var payload
    testHarness.mount({
        dom: `<button wire:click="someMethod('foo', 'bar')"></button>`,
        requestInterceptor: i => payload = i
    })

    document.querySelector('button').click()

    await wait(() => {
        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('someMethod')
        expect(payload.actionQueue[0].payload.params).toEqual(['foo', 'bar'])
    })
})

test('if a click and blur happen at the same time, the actions are queued and sent together', async () => {
    var payload
    testHarness.mount({
        dom: '<input wire:blur="onBlur"><button wire:click="onClick"></button>',
        requestInterceptor: i => payload = i
    })

    document.querySelector('input').focus()
    document.querySelector('button').click()
    document.querySelector('input').blur()

    await wait(() => {
        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('onClick')
        expect(payload.actionQueue[1].type).toEqual('callMethod')
        expect(payload.actionQueue[1].payload.method).toEqual('onBlur')
    })
})

test('two keydown events', async () => {
    var payload
    testHarness.mount({
        dom: '<button wire:keydown="someMethod" wire:keydown.enter="otherMethod"></button>',
        requestInterceptor: i => payload = i
    })

    fireEvent.keyDown(document.querySelector('button'), { key: 'Enter' })

    await wait(() => {
        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('someMethod')
        expect(payload.actionQueue[0].payload.params).toEqual([])
        expect(payload.actionQueue[1].type).toEqual('callMethod')
        expect(payload.actionQueue[1].payload.method).toEqual('otherMethod')
        expect(payload.actionQueue[1].payload.params).toEqual([])
    })
})

test('keydown.enter doesnt fire when other keys are pressed', async () => {
    var payload
    testHarness.mount({
        dom: '<button wire:keydown.enter="otherMethod"></button>',
        requestInterceptor: i => payload = i
    })

    fireEvent.keyDown(document.querySelector('button'), { key: 'Escape' })

    await timeout(10)

    expect(payload).toBeUndefined()
})

test('keyup.enter doesnt fire when other keys are pressed', async () => {
    var payload
    testHarness.mount({
        dom: '<button wire:keyup.enter="otherMethod"></button>',
        requestInterceptor: i => payload = i
    })

    fireEvent.keyUp(document.querySelector('button'), { key: 'Escape' })

    await timeout(10)

    expect(payload).toBeUndefined()
})

test('keyup.cmd.enter', async () => {
    var payload
    testHarness.mount({
        dom: '<button wire:keyup.cmd.enter="otherMethod"></button>',
        requestInterceptor: i => payload = i
    })

    fireEvent.keyUp(document.querySelector('button'), { metaKey: false, key: 'Enter' })

    await timeout(10)

    expect(payload).toBeUndefined()
})

test('init', async () => {
    var initHappened = false
    testHarness.mount({
        dom: '<div wire:id="123" wire:initial-data="{}" wire:init="someMethod"></div>',
        asRoot: true,
        requestInterceptor: () => { initHappened = true }
    })

    await timeout(10)

    expect(initHappened).toBeTruthy()
})

test('elements are marked as read-only or disabled during form submissions', async () => {
    var payload
    testHarness.mount({
        dom: `
            <form wire:submit.prevent="someMethod">
                <input type="text">
                <input type="checkbox">
                <input type="radio">
                <select></select>
                <textarea></textarea>
                <button type="submit"></button>
            </form>
        `,
        requestInterceptor: i => payload = i
    })

    document.querySelector('button').click()

    await wait(() => {
        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('someMethod')
        expect(payload.actionQueue[0].payload.params).toEqual([])
        expect(document.querySelector('button').disabled).toBeTruthy()
        expect(document.querySelector('select').disabled).toBeTruthy()
        expect(document.querySelector('input[type=checkbox]').disabled).toBeTruthy()
        expect(document.querySelector('input[type=radio]').disabled).toBeTruthy()
        expect(document.querySelector('input[type=text]').readOnly).toBeTruthy()
        expect(document.querySelector('textarea').readOnly).toBeTruthy()
    })
})

test('action parameters without space around comma', async () => {
    var payload
    testHarness.mount({
        dom: `<button wire:click="callSomething('foo','bar')"></button>`,
        requestInterceptor: i => payload = i
    })

    fireEvent.click(document.querySelector('button'))

    await wait(() => {
        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('callSomething')
        expect(payload.actionQueue[0].payload.params).toEqual(['foo', 'bar'])
    })
})

test('action parameters with space before comma', async () => {
    var payload
    testHarness.mount({
        dom: `<button wire:click="callSomething('foo' ,'bar')"></button>`,
        requestInterceptor: i => payload = i
    })

    fireEvent.click(document.querySelector('button'))

    await wait(() => {
        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('callSomething')
        expect(payload.actionQueue[0].payload.params).toEqual(['foo', 'bar'])
    })
})

test('action parameters with space after comma', async () => {
    var payload
    testHarness.mount({
        dom: `<button wire:click="callSomething('foo', 'bar')"></button>`,
        requestInterceptor: i => payload = i
    })

    fireEvent.click(document.querySelector('button'))

    await wait(() => {
        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('callSomething')
        expect(payload.actionQueue[0].payload.params).toEqual(['foo', 'bar'])
    })
})

test('action parameters with space around comma', async () => {
    var payload
    testHarness.mount({
        dom: `<button wire:click="callSomething('foo' , 'bar')"></button>`,
        requestInterceptor: i => payload = i
    })

    fireEvent.click(document.querySelector('button'))

    await wait(() => {
        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('callSomething')
        expect(payload.actionQueue[0].payload.params).toEqual(['foo', 'bar'])
    })
})

test('action parameters with space and comma inside will be handled', async () => {
    var payload
    testHarness.mount({
        dom: `<button wire:click="callSomething('foo, bar', true , 'baz',null,'x,y')"></button>`,
        requestInterceptor: i => payload = i
    })

    fireEvent.click(document.querySelector('button'))

    await wait(() => {
        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('callSomething')
        expect(payload.actionQueue[0].payload.params).toEqual(['foo, bar', true, 'baz', null, 'x,y'])
    })
})

test('action parameters must be separated by comma', async () => {
    var payload
    testHarness.mount({
        dom: `<button wire:click="callSomething('foo'|'bar')"></button>`,
        requestInterceptor: i => payload = i
    })

    fireEvent.click(document.querySelector('button'))

    await wait(() => {
        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('callSomething')
        expect(payload.actionQueue[0].payload.params).not.toEqual(['foo', 'bar'])
    })
})

test('action parameter can be empty', async () => {
    var payload
    testHarness.mount({
        dom: `<button wire:click="callSomething()"></button>`,
        requestInterceptor: i => payload = i
    })

    fireEvent.click(document.querySelector('button'))

    await wait(() => {
        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('callSomething')
        expect(payload.actionQueue[0].payload.params).toEqual([])
    })
})

test('action parameter can use double-quotes', async () => {
    var payload
    testHarness.mount({
        dom: `<button wire:click='callSomething("double-quotes are ugly", true)'></button>`,
        requestInterceptor: i => payload = i
    })

    fireEvent.click(document.querySelector('button'))

    await wait(() => {
        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('callSomething')
        expect(payload.actionQueue[0].payload.params).toEqual(['double-quotes are ugly', true])
    })
})

test('action parameters can include expressions', async () => {
    var payload
    mount(`<button wire:click="callSomething('foo', new Array('1','2'))"></button>`, i => payload = i)

    fireEvent.click(document.querySelector('button'))

    await wait(() => {
        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('callSomething')
        expect(payload.actionQueue[0].payload.params).toEqual(['foo', ['1','2']])
    })
})

test('debounce keyup event', async () => {
    var payload
    testHarness.mount({
        dom: '<input wire:keyup.debounce.50ms="someMethod"></button>',
        requestInterceptor: i => payload = i
    })

    fireEvent.keyUp(document.querySelector('input'), { key: 'x' })

    await timeout(1)

    expect(payload).toEqual(undefined)

    await timeout(60)

    expect(payload.actionQueue[0].payload.method).toEqual('someMethod')
})

test('debounce keyup event with key specified', async () => {
    var payload
    testHarness.mount({
        dom: '<input wire:keyup.x.debounce.50ms="someMethod"></button>',
        requestInterceptor: i => payload = i
    })

    fireEvent.keyUp(document.querySelector('input'), { key: 'k' })

    await timeout(5)

    expect(payload).toEqual(undefined)

    await timeout(60)

    expect(payload).toEqual(undefined)

    fireEvent.keyUp(document.querySelector('input'), { key: 'x' })

    await timeout(5)

    expect(payload).toEqual(undefined)

    await timeout(60)

    expect(payload.actionQueue[0].payload.method).toEqual('someMethod')
})

test('keydown event', async () => {
    var payload
    testHarness.mount({
        dom: '<input wire:keydown="someMethod"></button>',
        requestInterceptor: i => payload = i
    })

    fireEvent.keyDown(document.querySelector('input'), { key: 'x' })

    await wait(() => {

        expect(payload.actionQueue[0].type).toEqual('callMethod')
        expect(payload.actionQueue[0].payload.method).toEqual('someMethod')
        expect(payload.actionQueue[0].payload.params).toEqual([])
    })
})
